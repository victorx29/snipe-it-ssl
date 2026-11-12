<?php

namespace App\Http\Controllers\Accessories;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Category;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * SSL 4.1-4.2: sesiones y asistencia en accesorios existentes.
 * Las asistencias no son checkouts ni cantidades de inventario.
 */
class SslTrainingsController extends Controller
{
    private const AREA_NAMES = ['CAPACITACION_SSL', 'CAPACITACION_GH', 'CAPACITACION_TI'];

    private function trainingCategoryIds(): array
    {
        // Incluye categorías eliminadas para conservar registros históricos.
        return DB::table('categories')->where('category_type', 'accessory')
            ->whereIn('name', self::AREA_NAMES)->pluck('id')->all();
    }

    private function isTraining(Accessory $training): bool
    {
        return in_array($training->category_id, $this->trainingCategoryIds(), false);
    }

    private function availableCategories()
    {
        return Category::where('category_type', 'accessory')
            ->whereIn('name', self::AREA_NAMES)->orderBy('name')->get();
    }

    public function index(Request $request): View
    {
        $this->authorize('index', Accessory::class);

        // Se conserva el scope de compañía y todas las reglas FMCS del modelo.
        $query = Accessory::with('category', 'company', 'location')
            ->whereIn('accessories.category_id', $this->trainingCategoryIds());

        if ($request->filled('area') && in_array($request->query('area'), self::AREA_NAMES, true)) {
            $categoryName = $request->query('area');
            $ids = DB::table('categories')->where('name', $categoryName)
                ->where('category_type', 'accessory')->pluck('id')->all();
            $query->whereIn('accessories.category_id', $ids);
        }

        if (is_string($request->query('search')) && $request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('accessories.name', 'like', '%'.$search.'%')
                    ->orWhere('accessories.model_number', 'like', '%'.$search.'%');
            });
        }

        $query->withCount('sslAttendanceRecords as ssl_registered_participants_count')
            ->withCount(['sslAttendanceRecords as ssl_confirmed_attendance_count' => function ($q): void {
                $q->where('ssl_attendance_status', 'attended');
            }])
            ->withSum(['sslAttendanceRecords as ssl_confirmed_minutes_sum' => function ($q): void {
                $q->where('ssl_attendance_status', 'attended');
            }], 'ssl_attended_minutes');

        $trainings = $query->orderBy('ssl_training_starts_at', 'desc')
            ->orderBy('accessories.id', 'desc')->paginate(25)->withQueryString();

        return view('accessories.trainings-index', [
            'trainings' => $trainings,
            'categories' => $this->availableCategories(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Accessory::class);

        return view('accessories.training-form', [
            'training' => new Accessory,
            'categories' => $this->availableCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Accessory::class);
        $validated = $this->validatedInput($request);

        $training = new Accessory;
        $this->fillTraining($training, $validated);
        $training->qty = 0;       // Nunca usar stock como asistentes.
        $training->min_amt = 0;
        $training->requestable = false;
        $training->archived = false;
        $training->created_by = auth()->id();

        if (! $training->save()) {
            return redirect()->back()->withInput()->withErrors($training->getErrors());
        }

        return redirect()->route('ssl.trainings.show', $training->id)
            ->with('success', 'Sesión registrada. Puedes añadir participantes desde su ficha.');
    }

    public function show(Accessory $training): View
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('view', $training);
        $training->load('category', 'company', 'location');
        $attendances = $training->sslAttendanceRecords()
            ->with('sslAttendanceWorker')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $confirmed = $attendances->where('ssl_attendance_status', 'attended');
        $personMinutes = $training->ssl_training_status === 'completed'
            ? (int) $confirmed->sum('ssl_attended_minutes') : 0;

        return view('accessories.training-view', compact('training', 'attendances', 'personMinutes'));
    }

    public function edit(Accessory $training): View
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('update', $training);

        return view('accessories.training-form', [
            'training' => $training,
            'categories' => $this->availableCategories(),
        ]);
    }

    public function update(Request $request, Accessory $training): RedirectResponse
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('update', $training);
        $validated = $this->validatedInput($request);
        // Confirmed attendance must remain associated with a completed
        // session and with a duration no shorter than the credited minutes.
        $greatestCredited = (int) $training->sslAttendanceRecords()
            ->where('ssl_attendance_status', 'attended')
            ->max('ssl_attended_minutes');
        if ($greatestCredited > 0 && $validated['ssl_training_status'] !== 'completed') {
            throw ValidationException::withMessages([
                'ssl_training_status' => 'No se puede cambiar de Realizada mientras existan asistencias confirmadas.',
            ]);
        }
        if ($greatestCredited > (int) $validated['ssl_training_duration_minutes']) {
            throw ValidationException::withMessages([
                'ssl_training_duration_minutes' => 'La duración no puede ser menor que los minutos ya acreditados a un participante.',
            ]);
        }
        $this->fillTraining($training, $validated);

        // No se escribe qty, no se alteran órdenes ni los checkouts previos.
        if (! $training->save()) {
            return redirect()->back()->withInput()->withErrors($training->getErrors());
        }

        return redirect()->route('ssl.trainings.show', $training->id)
            ->with('success', 'Sesión de capacitación actualizada.');
    }

    /**
     * Inscribe a worker without dispatching CheckoutableCheckedOut, generating
     * acceptance requests or decrementing any accessory stock. The parent row
     * lock serializes competing inserts for the same worker/session.
     */
    public function registerParticipant(Request $request, Accessory $training): RedirectResponse
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('update', $training);
        if ($training->ssl_training_status === 'cancelled') {
            return redirect()->back()->with('error', 'No se pueden inscribir participantes en una sesión cancelada.');
        }
        if (! $training->ssl_training_starts_at || ! $training->ssl_training_duration_minutes) {
            return redirect()->back()->with('error', 'Completa los datos estructurados de la sesión antes de inscribir participantes.');
        }

        $data = $request->validate([
            'assigned_user' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $worker = User::query()->find($data['assigned_user']);
        if (! $worker) {
            throw ValidationException::withMessages(['assigned_user' => 'El trabajador no está disponible para el usuario actual.']);
        }
        if (Setting::getSettings()?->full_multiple_companies_support == '1' && $training->company_id
            && ! $worker->companies()->where('companies.id', $training->company_id)->exists()) {
            throw ValidationException::withMessages(['assigned_user' => 'El trabajador no pertenece a la compañía de esta sesión.']);
        }

        $alreadyRegistered = false;
        DB::transaction(function () use ($training, $worker, $data, &$alreadyRegistered): void {
            $locked = Accessory::whereKey($training->id)->lockForUpdate()->firstOrFail();
            if ($locked->ssl_training_status === 'cancelled' || ! $locked->ssl_training_starts_at) {
                throw ValidationException::withMessages(['assigned_user' => 'La capacitación ha sido cancelada o no tiene fecha estructurada.']);
            }
            $alreadyRegistered = AccessoryCheckout::query()
                ->where('accessory_id', $locked->id)
                ->where('assigned_type', User::class)
                ->where('assigned_to', $worker->id)
                ->whereNotNull('ssl_attendance_status')
                ->exists();
            if ($alreadyRegistered) {
                return;
            }

            $attendance = new AccessoryCheckout;
            $attendance->accessory_id = $locked->id;
            $attendance->assigned_type = User::class;
            $attendance->assigned_to = $worker->id;
            $attendance->created_by = auth()->id();
            $attendance->ssl_attendance_status = 'registered';
            $attendance->ssl_attended_minutes = 0;
            $attendance->note = $data['note'] ?? null;
            $attendance->save();
        });

        if ($alreadyRegistered) {
            return redirect()->route('ssl.trainings.show', $training->id)
                ->with('warning', 'Este trabajador ya está inscrito en la sesión. No se duplicó su registro.');
        }
        Log::info('SSL training participant registered', ['training_id' => $training->id, 'user_id' => $worker->id, 'by' => auth()->id()]);

        return redirect()->route('ssl.trainings.show', $training->id)->with('success', 'Participante inscrito. La asistencia aún no está confirmada.');
    }

    /** Confirm status and credited minutes independently for each person. */
    public function updateParticipant(Request $request, Accessory $training, AccessoryCheckout $attendance): RedirectResponse
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('update', $training);
        $this->assertAttendanceBelongsToTraining($training, $attendance);

        $data = $request->validate([
            'ssl_attendance_status' => ['required', Rule::in(['registered', 'attended', 'absent', 'excused'])],
            'ssl_attended_minutes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $status = $data['ssl_attendance_status'];
        if ($status !== 'registered' && $training->ssl_training_status !== 'completed') {
            throw ValidationException::withMessages(['ssl_attendance_status' => 'Marca primero la sesión como Realizada para confirmar asistencia o inasistencia.']);
        }
        $minutes = $status === 'attended'
            ? (int) ($data['ssl_attended_minutes'] ?? $training->ssl_training_duration_minutes)
            : 0;
        if ($status === 'attended' && ($minutes < 1 || $minutes > (int) $training->ssl_training_duration_minutes)) {
            throw ValidationException::withMessages(['ssl_attended_minutes' => 'Los minutos acreditados deben estar entre 1 y la duración efectiva de la sesión.']);
        }

        $old = $attendance->ssl_attendance_status;
        DB::transaction(function () use ($training, $attendance, $data, $status, $minutes): void {
            $lockedTraining = Accessory::whereKey($training->id)->lockForUpdate()->firstOrFail();
            $lockedAttendance = AccessoryCheckout::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $this->assertAttendanceBelongsToTraining($lockedTraining, $lockedAttendance);
            if ($status !== 'registered' && $lockedTraining->ssl_training_status !== 'completed') {
                throw ValidationException::withMessages(['ssl_attendance_status' => 'La sesión ya no figura como Realizada.']);
            }
            if ($status === 'attended' && $minutes > (int) $lockedTraining->ssl_training_duration_minutes) {
                throw ValidationException::withMessages(['ssl_attended_minutes' => 'La duración de la sesión cambió. Revisa los minutos.']);
            }
            $lockedAttendance->ssl_attendance_status = $status;
            $lockedAttendance->ssl_attended_minutes = $minutes;
            $lockedAttendance->ssl_attendance_confirmed_at = $status === 'attended' ? now() : null;
            $lockedAttendance->ssl_attendance_confirmed_by = $status === 'attended' ? auth()->id() : null;
            $lockedAttendance->note = $data['note'] ?? null;
            $lockedAttendance->save();
        });
        Log::info('SSL training attendance updated', [
            'training_id' => $training->id,
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->assigned_to,
            'from' => $old,
            'to' => $status,
            'minutes' => $minutes,
            'by' => auth()->id(),
        ]);

        return redirect()->route('ssl.trainings.show', $training->id)->with('success', 'Asistencia actualizada.');
    }

    /** Only unconfirmed enrolments may be withdrawn. Confirmed history stays. */
    public function removeParticipant(Accessory $training, AccessoryCheckout $attendance): RedirectResponse
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('update', $training);
        $this->assertAttendanceBelongsToTraining($training, $attendance);

        $removed = false;
        DB::transaction(function () use ($training, $attendance, &$removed): void {
            Accessory::whereKey($training->id)->lockForUpdate()->firstOrFail();
            $locked = AccessoryCheckout::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $this->assertAttendanceBelongsToTraining($training, $locked);
            if ($locked->ssl_attendance_status !== 'registered') {
                return;
            }
            $locked->delete();
            $removed = true;
        });
        if (! $removed) {
            return redirect()->back()->with('error', 'Solo se pueden retirar inscripciones todavía no confirmadas.');
        }
        Log::info('SSL training enrolment withdrawn', ['training_id' => $training->id, 'attendance_id' => $attendance->id, 'by' => auth()->id()]);

        return redirect()->route('ssl.trainings.show', $training->id)->with('success', 'Inscripción retirada.');
    }

    private function assertAttendanceBelongsToTraining(Accessory $training, AccessoryCheckout $attendance): void
    {
        abort_unless($attendance->accessory_id == $training->id
            && $attendance->assigned_type === User::class
            && $attendance->ssl_attendance_status !== null, 404);
    }

    private function validatedInput(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(function ($q): void {
                    $q->where('category_type', 'accessory')
                        ->whereIn('name', self::AREA_NAMES)->whereNull('deleted_at');
                }),
            ],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'model_number' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
            // datetime-local envia un valor sin zona horaria en formato ISO local.
            'ssl_training_starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ssl_training_ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:ssl_training_starts_at'],
            'ssl_training_duration_minutes' => ['required', 'integer', 'min:1', 'max:1000000'],
            'ssl_training_status' => ['required', Rule::in(['planned', 'completed', 'cancelled'])],
            'ssl_training_modality' => ['required', Rule::in(['presencial', 'virtual', 'mixta'])],
            'ssl_training_facilitator' => ['nullable', 'string', 'max:191'],
        ], [
            'ssl_training_ends_at.after' => 'La finalización debe ser posterior al inicio de la sesión.',
            'category_id.exists' => 'Selecciona una categoría CAPACITACION_SSL, CAPACITACION_GH o CAPACITACION_TI de tipo Accesorio.',
        ]);

        $starts = Carbon::createFromFormat('Y-m-d\TH:i', $validated['ssl_training_starts_at']);
        $ends = Carbon::createFromFormat('Y-m-d\TH:i', $validated['ssl_training_ends_at']);
        if ((int) $validated['ssl_training_duration_minutes'] > $starts->diffInMinutes($ends)) {
            throw ValidationException::withMessages([
                'ssl_training_duration_minutes' => 'La duración efectiva no puede superar el tiempo transcurrido entre inicio y finalización.',
            ]);
        }

        return $validated;
    }

    private function fillTraining(Accessory $training, array $data): void
    {
        $training->name = $data['name'];
        $training->category_id = $data['category_id'];
        $training->company_id = Company::getIdForCurrentUser($data['company_id'] ?? null);
        $training->location_id = $data['location_id'] ?? null;
        $training->model_number = $data['model_number'] ?? null;
        $training->notes = $data['notes'] ?? null;
        $training->ssl_training_starts_at = Carbon::createFromFormat('Y-m-d\TH:i', $data['ssl_training_starts_at'])->second(0);
        $training->ssl_training_ends_at = Carbon::createFromFormat('Y-m-d\TH:i', $data['ssl_training_ends_at'])->second(0);
        $training->ssl_training_duration_minutes = (int) $data['ssl_training_duration_minutes'];
        $training->ssl_training_status = $data['ssl_training_status'];
        $training->ssl_training_modality = $data['ssl_training_modality'];
        $training->ssl_training_facilitator = $data['ssl_training_facilitator'] ?? null;
    }
}
