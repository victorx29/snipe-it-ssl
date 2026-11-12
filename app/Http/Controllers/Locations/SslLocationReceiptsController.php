<?php

namespace App\Http\Controllers\Locations;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * SSL 5.3: impresion HTML de activos actualmente asignados a una sede.
 * No realiza checkouts, no modifica movimientos y no sustituye la pantalla nativa.
 */
class SslLocationReceiptsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view', Location::class);
        $this->authorize('view', Asset::class);

        $request->validate(['q' => 'nullable|string|max:100']);

        // Mantener los scopes del modelo y aplicar ademas acceso por instancia/FMCS.
        // La lista es de consulta: nunca usamos withoutGlobalScopes().
        $query = Location::query()->with(['company', 'manager'])->orderBy('name');

        if ($request->filled('q')) {
            $query->where('locations.name', 'like', '%'.$request->input('q').'%');
        }

        $locations = $query->get()
            ->filter(fn (Location $location) =>
                Company::isCurrentUserHasAccess($location) && Gate::allows('view', $location)
            )
            ->values();

        return view('locations.ssl-location-assets-index', compact('locations'));
    }

    public function show(int $location): View
    {
        $this->authorize('view', Location::class);
        $this->authorize('view', Asset::class);

        $locationModel = Location::query()->with(['company', 'manager'])->findOrFail($location);
        $this->authorize('view', $locationModel);
        abort_unless(Company::isCurrentUserHasAccess($locationModel), 403);

        // IMPORTANTE: location_id indica ubicacion actual, no la persona/entidad
        // receptora. Solo se imprimen activos cuya asignacion directa es la sede.
        // Conservar SoftDeletes y scopes corporativos; no usar withTrashed().
        $assets = Asset::query()
            ->with(['model.category', 'company'])
            ->where('assets.assigned_type', $locationModel->getMorphClass())
            ->where('assets.assigned_to', $locationModel->id)
            ->orderBy('assets.asset_tag')
            ->get()
            ->filter(fn (Asset $asset) => Gate::allows('view', $asset))
            ->values();

        return view('locations.ssl-location-assets-receipt', [
            'location' => $locationModel,
            'assets' => $assets,
            'printedAt' => now(),
            'snipeSettings' => Setting::getSettings(),
        ]);
    }
}
