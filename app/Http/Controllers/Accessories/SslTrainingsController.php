<?php

namespace App\Http\Controllers\Accessories;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Category;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * SSL 4.1: registro de sesiones de capacitación reutilizando Accessory.
 * NO utiliza checkout ni qty como asistentes; la asistencia es otra etapa.
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
            ->with('success', 'Sesión registrada. La asistencia se habilitará en la etapa 4.2.');
    }

    public function show(Accessory $training): View
    {
        abort_unless($this->isTraining($training), 404);
        $this->authorize('view', $training);
        $training->load('category', 'company', 'location');

        return view('accessories.training-view', compact('training'));
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
        $this->fillTraining($training, $validated);

        // No se escribe qty, no se alteran órdenes ni los checkouts previos.
        if (! $training->save()) {
            return redirect()->back()->withInput()->withErrors($training->getErrors());
        }

        return redirect()->route('ssl.trainings.show', $training->id)
            ->with('success', 'Sesión de capacitación actualizada.');
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
