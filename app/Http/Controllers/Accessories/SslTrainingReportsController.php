<?php

namespace App\Http\Controllers\Accessories;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSL 4.3: consultas consolidadas de sesiones y asistencias confirmadas.
 * Usa exclusivamente las relaciones de la etapa 4.2; no crea checkouts,
 * modifica inventario ni consulta datos personales de los participantes.
 */
class SslTrainingReportsController extends Controller
{
    private const AREAS = ['CAPACITACION_SSL', 'CAPACITACION_GH', 'CAPACITACION_TI'];

    public function index(Request $request): View
    {
        $this->authorize('index', Accessory::class);

        $filters = $this->validatedFilters($request);
        $categoryNames = $this->categoryNames();
        $query = $this->filteredSessions($filters, $categoryNames);
        [$areas, $totals] = $this->summaries(clone $query, $filters, $categoryNames);

        $trainings = $this->withAttendanceMetrics(clone $query)
            ->with(['category', 'company', 'location'])
            ->orderByDesc('accessories.ssl_training_starts_at')
            ->orderByDesc('accessories.id')
            ->paginate(25)->withQueryString();

        // Las opciones vienen SOLO de sesiones accesibles por el scope FMCS
        // del modelo, no de la lista global de compañías/ubicaciones.
        $available = Accessory::query()->whereIn('accessories.category_id', array_keys($categoryNames));
        $companyIds = (clone $available)->whereNotNull('accessories.company_id')
            ->distinct()->pluck('accessories.company_id')->all();
        $locationIds = (clone $available)->whereNotNull('accessories.location_id')
            ->distinct()->pluck('accessories.location_id')->all();

        return view('accessories.training-report', [
            'filters' => $filters,
            'areas' => $areas,
            'totals' => $totals,
            'trainings' => $trainings,
            'companies' => Company::query()->whereIn('id', $companyIds)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->whereIn('id', $locationIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('index', Accessory::class);

        $filters = $this->validatedFilters($request);
        $level = $filters['level'] ?? 'sessions';
        $categoryNames = $this->categoryNames();
        $query = $this->filteredSessions($filters, $categoryNames);
        $filename = 'ssl-capacitaciones-'.$level.'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query, $filters, $categoryNames, $level): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            // BOM para Excel en Windows; separador ; y decimales con punto
            // para importaciones de Power BI con configuración explícita.
            fwrite($output, "\xEF\xBB\xBF");

            if ($level === 'areas') {
                $this->csvRow($output, ['Área', 'Sesiones', 'Realizadas', 'Inscritos', 'Asistieron',
                    'Ausentes', 'Justificados', 'Pendientes', 'Horas-hombre', 'Asistencia (%)']);
                [$areas, $totals] = $this->summaries(clone $query, $filters, $categoryNames);
                foreach ($areas as $row) {
                    $this->csvSummary($output, $row);
                }
                $totals['area'] = 'TOTAL';
                $this->csvSummary($output, $totals);
            } else {
                $this->csvRow($output, ['ID sesión', 'Área', 'Sesión', 'Referencia', 'Empresa', 'Sede',
                    'Inicio', 'Finalización', 'Duración (minutos)', 'Estado', 'Inscritos',
                    'Asistieron', 'Ausentes', 'Justificados', 'Pendientes',
                    'Horas-hombre', 'Asistencia (%)']);
                $this->withAttendanceMetrics(clone $query)
                    ->with(['category', 'company', 'location'])
                    ->chunkById(200, function ($sessions) use ($output): void {
                        foreach ($sessions as $session) {
                            $metrics = $this->sessionMetrics($session);
                            $this->csvRow($output, [
                                $session->id,
                                User::sslTrainingAreaLabel($session->category?->name),
                                $session->name,
                                $session->model_number,
                                $session->company?->name,
                                $session->location?->name,
                                $session->ssl_training_starts_at?->format('Y-m-d H:i'),
                                $session->ssl_training_ends_at?->format('Y-m-d H:i'),
                                $session->ssl_training_duration_minutes,
                                $this->statusLabel($session->ssl_training_status),
                                $metrics['registered'], $metrics['attended'], $metrics['absent'],
                                $metrics['excused'], $metrics['pending'],
                                $this->decimalHours($metrics['credited_minutes']),
                                $this->attendanceRate($metrics),
                            ]);
                        }
                    }, 'accessories.id', 'id');
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'area' => ['nullable', Rule::in(self::AREAS)],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['planned', 'completed', 'cancelled', 'legacy'])],
            'search' => ['nullable', 'string', 'max:191'],
            'level' => ['nullable', Rule::in(['areas', 'sessions'])],
        ]);
    }

    /** @return array<int, string> */
    private function categoryNames(): array
    {
        // Incluimos categorías borradas lógicamente para no ocultar sesiones
        // históricas. Es la misma regla de la pantalla de Capacitaciones 4.2.
        return DB::table('categories')->where('category_type', 'accessory')
            ->whereIn('name', self::AREAS)->pluck('name', 'id')->all();
    }

    private function filteredSessions(array $filters, array $categoryNames): Builder
    {
        // El scope Companyable del modelo Accessory sigue activo.
        $query = Accessory::query()->whereIn('accessories.category_id', array_keys($categoryNames));

        if (! empty($filters['area'])) {
            $ids = array_keys(array_filter($categoryNames, fn ($name) => $name === $filters['area']));
            $query->whereIn('accessories.category_id', $ids);
        }
        if (! empty($filters['company_id'])) {
            $query->where('accessories.company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['location_id'])) {
            $query->where('accessories.location_id', (int) $filters['location_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('accessories.ssl_training_starts_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('accessories.ssl_training_starts_at', '<=', $filters['to']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'legacy') {
                $query->whereNull('accessories.ssl_training_status');
            } else {
                $query->where('accessories.ssl_training_status', $filters['status']);
            }
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('accessories.name', 'like', '%'.$search.'%')
                        ->orWhere('accessories.model_number', 'like', '%'.$search.'%');
                });
            }
        }
        return $query;
    }

    private function withAttendanceMetrics(Builder $query): Builder
    {
        return $query
            ->withCount('sslAttendanceRecords as ssl_report_registered_count')
            ->withCount(['sslAttendanceRecords as ssl_report_attended_count' => function ($q): void {
                $q->where('ssl_attendance_status', 'attended');
            }])
            ->withCount(['sslAttendanceRecords as ssl_report_absent_count' => function ($q): void {
                $q->where('ssl_attendance_status', 'absent');
            }])
            ->withCount(['sslAttendanceRecords as ssl_report_excused_count' => function ($q): void {
                $q->where('ssl_attendance_status', 'excused');
            }])
            ->withCount(['sslAttendanceRecords as ssl_report_pending_count' => function ($q): void {
                $q->where('ssl_attendance_status', 'registered');
            }])
            ->withSum(['sslAttendanceRecords as ssl_report_minutes_sum' => function ($q): void {
                $q->where('ssl_attendance_status', 'attended');
            }], 'ssl_attended_minutes');
    }

    /** @return array<string, int> */
    private function sessionMetrics(Accessory $session): array
    {
        // Solo las sesiones realizadas acreditan asistencia y horas-hombre.
        $completed = $session->ssl_training_status === 'completed';
        $registered = (int) ($session->ssl_report_registered_count ?? 0);

        return [
            'sessions' => 1,
            'completed_sessions' => $completed ? 1 : 0,
            'registered' => $registered,
            'completed_registrations' => $completed ? $registered : 0,
            'attended' => $completed ? (int) ($session->ssl_report_attended_count ?? 0) : 0,
            'absent' => $completed ? (int) ($session->ssl_report_absent_count ?? 0) : 0,
            'excused' => $completed ? (int) ($session->ssl_report_excused_count ?? 0) : 0,
            'pending' => (int) ($session->ssl_report_pending_count ?? 0),
            'credited_minutes' => $completed ? (int) ($session->ssl_report_minutes_sum ?? 0) : 0,
        ];
    }

    /** @return array<string, int|string> */
    private function emptySummary(string $area): array
    {
        return ['area' => $area, 'sessions' => 0, 'completed_sessions' => 0,
            'registered' => 0, 'completed_registrations' => 0, 'attended' => 0,
            'absent' => 0, 'excused' => 0, 'pending' => 0, 'credited_minutes' => 0];
    }

    private function accumulate(array &$row, array $metrics): void
    {
        foreach ($metrics as $key => $amount) {
            $row[$key] += $amount;
        }
    }

    /** @return array{0: array<string, array>, 1: array} */
    private function summaries(Builder $query, array $filters, array $categoryNames): array
    {
        $areas = [];
        foreach (self::AREAS as $area) {
            if (empty($filters['area']) || $filters['area'] === $area) {
                $areas[$area] = $this->emptySummary($area);
            }
        }
        $totals = $this->emptySummary('TOTAL');

        $this->withAttendanceMetrics($query)->chunkById(200, function ($sessions) use (&$areas, &$totals, $categoryNames): void {
            foreach ($sessions as $session) {
                $area = $categoryNames[$session->category_id] ?? null;
                if ($area === null || ! isset($areas[$area])) {
                    continue;
                }
                $metrics = $this->sessionMetrics($session);
                $this->accumulate($areas[$area], $metrics);
                $this->accumulate($totals, $metrics);
            }
        }, 'accessories.id', 'id');

        return [$areas, $totals];
    }

    public function attendanceRate(array $metrics): string
    {
        if (! ($metrics['completed_registrations'] ?? 0)) {
            return '—';
        }
        return number_format(100 * $metrics['attended'] / $metrics['completed_registrations'], 1, '.', '');
    }

    private function decimalHours(int $minutes): string
    {
        return number_format($minutes / 60, 2, '.', '');
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'planned' => 'Programada',
            'completed' => 'Realizada',
            'cancelled' => 'Cancelada',
            default => 'Referencia anterior',
        };
    }

    private function csvSummary($output, array $row): void
    {
        $this->csvRow($output, [
            $row['area'] === 'TOTAL' ? 'TOTAL' : User::sslTrainingAreaLabel($row['area']),
            $row['sessions'], $row['completed_sessions'], $row['registered'],
            $row['attended'], $row['absent'], $row['excused'], $row['pending'],
            $this->decimalHours($row['credited_minutes']), $this->attendanceRate($row),
        ]);
    }

    private function csvRow($output, array $cells): void
    {
        $safe = array_map(function ($value): string {
            $text = (string) ($value ?? '');
            // Evita que nombres/códigos administrados por usuarios se interpreten
            // como fórmulas al abrir el CSV con Excel o herramientas similares.
            return preg_match('/^[\x00-\x20]*[=+\-@]/', $text) ? "'".$text : $text;
        }, $cells);
        fputcsv($output, $safe, ';', '"', '');
    }
}
