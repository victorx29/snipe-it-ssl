<?php

namespace App\Http\Controllers\Licenses;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\License;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use League\Csv\EscapeFormula;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This controller handles all actions related to Licenses for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class LicensesController extends Controller
{
    /**
     * Returns a view that invokes the ajax tables which actually contains
     * the content for the licenses listing, which is generated in getDatatable.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @see LicensesController::getDatatable() method that generates the JSON response
     * @since [v1.0]
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function index()
    {
        $this->authorize('view', License::class);

        return view('licenses/index');
    }

    /**
     * SSL: live renewal reminders, limited to the currently active validity
     * period in each chain. These are on-screen notices, not email jobs.
     * Legacy licenses and superseded permits are deliberately excluded.
     */
    public function sslAlerts(Request $request): View
    {
        $this->authorize('view', License::class);

        $request->validate([
            'q' => 'nullable|string|max:120',
            'state' => 'nullable|in:all,expired,due,in_progress,scheduled,not_configured,no_expiration',
            'company_id' => 'nullable|integer|min:1',
            'location_id' => 'nullable|integer|min:1',
        ]);

        $search = trim((string) $request->input('q', ''));
        $selectedState = (string) $request->input('state', 'all');
        $selectedCompany = $request->filled('company_id') ? (int) $request->input('company_id') : null;
        $selectedLocation = $request->filled('location_id') ? (int) $request->input('location_id') : null;

        $query = License::query()
            ->with(['company', 'location', 'manufacturer'])
            ->where('licenses.ssl_is_permit', true)
            // withTrashed() in sslNextPermit ensures historical rows remain
            // excluded even if a renewed record was subsequently archived.
            ->whereDoesntHave('sslNextPermit')
            ->where(function ($query) {
                $query->whereNull('licenses.ssl_process_status')
                    ->orWhere('licenses.ssl_process_status', '!=', 'renewed');
            });

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('licenses.name', 'like', $like)
                    ->orWhereHas('location', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('manufacturer', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('company', fn ($q) => $q->where('name', 'like', $like));
            });
        }

        // Apply both object-level authorization and the existing FMCS rules
        // BEFORE producing selectors, counters or pagination totals.
        $visiblePermits = $query->get()
            ->filter(fn (License $permit) => Company::isCurrentUserHasAccess($permit)
                && Gate::allows('view', $permit))
            ->values();

        $companies = $visiblePermits->filter(fn (License $permit) => $permit->company !== null)
            ->mapWithKeys(fn (License $permit) => [(int) $permit->company_id => $permit->company->name])
            ->sort()->all();
        $locations = $visiblePermits->filter(fn (License $permit) => $permit->location !== null)
            ->mapWithKeys(fn (License $permit) => [(int) $permit->location_id => $permit->location->name])
            ->sort()->all();

        $filteredPermits = $visiblePermits->filter(function (License $permit) use ($selectedCompany, $selectedLocation): bool {
            return ($selectedCompany === null || (int) $permit->company_id === $selectedCompany)
                && ($selectedLocation === null || (int) $permit->location_id === $selectedLocation);
        });

        $states = ['expired', 'due', 'in_progress', 'scheduled', 'not_configured', 'no_expiration'];
        $counts = array_fill_keys($states, 0);
        $entries = $filteredPermits->map(function (License $permit) use (&$counts): array {
            $state = $permit->sslNoticeState();
            if (array_key_exists($state, $counts)) {
                $counts[$state]++;
            }

            return [
                'permit' => $permit,
                'state' => $state,
                'notice_date' => $permit->sslNoticeStartDate(),
            ];
        });

        $priorities = array_flip($states);
        $ordered = $entries
            ->filter(fn (array $entry) => $selectedState === 'all' || $entry['state'] === $selectedState)
            ->sortBy(fn (array $entry) => sprintf('%02d-%s-%010d',
                $priorities[$entry['state']] ?? 99,
                $entry['permit']->expiration_date?->format('Ymd') ?? '99999999',
                (int) $entry['permit']->id
            ))
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $ordered->forPage($page, $perPage)->values(),
            $ordered->count(),
            $perPage,
            $page,
            ['path' => route('licenses.ssl.alerts'), 'query' => $request->except('page')]
        );

        return view('licenses.ssl-alerts', compact(
            'rows', 'counts', 'companies', 'locations',
            'search', 'selectedState', 'selectedCompany', 'selectedLocation'
        ))->with('totalCurrent', $filteredPermits->count());
    }

    /**
     * Returns a form view that allows an admin to create a new licence.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @see AccessoriesController::getDatatable() method that generates the JSON response
     * @since [v1.0]
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', License::class);
        $maintained_list = [
            '' => 'Maintained',
            '1' => 'Yes',
            '0' => 'No',
        ];

        return view('licenses/edit')
            ->with('depreciation_list', Helper::depreciationList())
            ->with('maintained_list', $maintained_list)
            ->with('item', new License);
    }

    /**
     * Validates and stores the license form data submitted from the new
     * license form.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @see LicensesController::getCreate() method that provides the form view
     * @since [v1.0]
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', License::class);
        // All newly registered SSL permissions are per-site and do not assign seats.
        $this->validateSslPermitInput($request, true);
        // create a new model instance
        $license = new License;
        $license->ssl_is_permit = true;
        $license->location_id = $request->input('location_id');
        $license->ssl_notice_value = $request->input('ssl_notice_value');
        $license->ssl_notice_unit = $request->input('ssl_notice_unit');
        $license->ssl_process_status = $request->input('ssl_process_status', 'pending');
        $license->ssl_issued_at = $request->input('ssl_issued_at');
        // Save the license data
        $license->company_id = Company::getIdForCurrentUser($request->input('company_id'));
        $license->depreciation_id = $request->input('depreciation_id');
        $license->expiration_date = $request->input('expiration_date');
        $license->license_email = $request->input('license_email');
        $license->license_name = $request->input('license_name');
        $license->maintained = $request->input('maintained', 0);
        $license->manufacturer_id = $request->input('manufacturer_id');
        $license->name = $request->input('name');
        $license->notes = $request->input('notes');
        $license->order_number = $request->input('order_number');
        $license->purchase_cost = $request->input('purchase_cost');
        $license->purchase_date = $request->input('purchase_date');
        $license->purchase_order = $request->input('purchase_order');
        $license->purchase_order = $request->input('purchase_order');
        $license->reassignable = $license->ssl_is_permit ? 0 : $request->input('reassignable', 0);
        $license->seats = 1; // Required internally by the existing License model.
        $license->serial = $request->input('serial');
        $license->supplier_id = $request->input('supplier_id');
        $license->category_id = $request->input('category_id');
        $license->termination_date = $request->input('termination_date');
        $license->created_by = auth()->id();
        $license->min_amt = $request->input('min_amt');
        // Unchecked checkboxes are omitted from the POST body; coerce
        // absence to false so the flag really flips off. The setter
        // normalizes the truthy branch.
        $license->requestable = $license->ssl_is_permit ? false : $request->input('requestable', false);

        if ($request->input('redirect_option') === 'back') {
            session()->put(['redirect_option' => 'index']);
        } else {
            session()->put(['redirect_option' => $request->input('redirect_option')]);
        }

        if ($license->save()) {
            return Helper::getRedirectOption($request, $license->id, 'Licenses')
                ->with('success', trans('admin/licenses/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($license->getErrors());
    }

    /**
     * Returns a form with existing license data to allow an admin to
     * update license information.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function edit(License $license)
    {

        $this->authorize('update', $license);
        if ($license->ssl_is_permit && $license->sslNextPermit()->exists()) {
            return redirect()->route('licenses.show', $license)->with('error', trans('admin/licenses/general.ssl_history_readonly'));
        }
        if ($safeReferer = Helper::sameOriginUrl(url()->previous())) {
            session()->put('url.intended', $safeReferer);
        }
        $maintained_list = [
            '' => 'Maintained',
            '1' => 'Yes',
            '0' => 'No',
        ];

        return view('licenses/edit')
            ->with('item', $license)
            ->with('depreciation_list', Helper::depreciationList())
            ->with('maintained_list', $maintained_list);
    }

    /**
     * Validates and stores the license form data submitted from the edit
     * license form.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @see LicensesController::getEdit() method that provides the form view
     * @since [v1.0]
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, License $license)
    {

        $this->authorize('update', $license);

        $this->validateSslPermitInput($request, (bool) $license->ssl_is_permit);
        if ($license->ssl_is_permit && $license->sslNextPermit()->exists()) {
            return redirect()->route('licenses.show', $license)->with('error', trans('admin/licenses/general.ssl_history_readonly'));
        }
        $license->ssl_issued_at = $request->input('ssl_issued_at');
        $license->location_id = $request->input('location_id');
        $license->ssl_notice_value = $request->input('ssl_notice_value');
        $license->ssl_notice_unit = $request->input('ssl_notice_unit');
        $license->ssl_process_status = $request->input('ssl_process_status');

        $license->company_id = Company::getIdForCurrentUser($request->input('company_id'));
        $license->depreciation_id = $request->input('depreciation_id');
        $license->expiration_date = $request->input('expiration_date');
        $license->license_email = $request->input('license_email');
        $license->license_name = $request->input('license_name');
        $license->maintained = $request->input('maintained', 0);
        $license->name = $request->input('name');
        $license->notes = $request->input('notes');
        $license->order_number = $request->input('order_number');
        $license->purchase_cost = $request->input('purchase_cost');
        $license->purchase_date = $request->input('purchase_date');
        $license->purchase_order = $request->input('purchase_order');
        $license->reassignable = $license->ssl_is_permit ? 0 : $request->input('reassignable', 0);
        $license->serial = $request->input('serial');
        $license->termination_date = $request->input('termination_date');
        // Never overwrite or shrink historic seats when editing a permit.
        if (! $license->ssl_is_permit) {
            $license->seats = e($request->input('seats'));
        }
        $license->manufacturer_id = $request->input('manufacturer_id');
        $license->supplier_id = $request->input('supplier_id');
        $license->category_id = $request->input('category_id');
        $license->min_amt = $request->input('min_amt');
        // Unchecked checkbox is omitted from the POST body; coerce
        // absence to false so unchecking really turns the flag off.
        $license->requestable = $license->ssl_is_permit ? false : $request->input('requestable', false);

        session()->put(['redirect_option' => $request->input('redirect_option')]);

        if ($license->save()) {
            return Helper::getRedirectOption($request, $license->id, 'Licenses')
                ->with('success', trans('admin/licenses/message.update.success'));
        }

        // If we can't adjust the number of seats, the error is flashed to the session by the event handler in License.php
        return redirect()->back()->withInput()->withErrors($license->getErrors());
    }

    /**
     * Checks to see whether the selected license can be deleted, and
     * if it can, marks it as deleted.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(License $license)
    {
        // Check if the license exists
        if (is_null($license = License::find($license->id))) {
            // Redirect to the license management page
            return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.not_found'));
        }

        $this->authorize('delete', $license);
        if ($license->ssl_is_permit) {
            return redirect()->route('licenses.show', $license)->with('error', trans('admin/licenses/general.ssl_delete_protected'));
        }

        if ($license->assigned_seats_count == 0) {
            // Delete the license and the associated license seats
            DB::table('license_seats')
                ->where('license_id', $license->id)
                ->update(['assigned_to' => null, 'asset_id' => null]);

            $licenseSeats = $license->licenseseats();
            $licenseSeats->delete();
            $license->delete();

            // Redirect to the licenses management page
            return redirect()->route('licenses.index')->with('success', trans('admin/licenses/message.delete.success'));
            // Redirect to the license management page
        }

        // There are still licenses in use.
        return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.assoc_users'));
    }

    /**
     * Makes the license detail page.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function show(License $license)
    {
        $license = License::with('assignedusers')->withCount('freeSeats as free_seats_count')->find($license->id);

        $users_count = User::where('autoassign_licenses', '1')->count();

        $total_seats_count = (int) $license->totalSeatsByLicenseID();
        $available_seats_count = $license->availCount()->count();
        $unreassignable_seats_count = License::unReassignableCount($license);

        if (! $license->reassignable) {
            $checkedout_seats_count = ($total_seats_count - $available_seats_count - $unreassignable_seats_count);
        } else {
            $checkedout_seats_count = ($total_seats_count - $available_seats_count);
        }
        if ($license->isInactive() && ! $license->ssl_is_permit) {
            session()->flash('warning', (trans('admin/licenses/message.checkout.license_is_inactive')));
        }

        $this->authorize('view', $license);

        // List all vigencias of this permit. Each is a separate row and owns its own uploads.
        $sslRenewalHistory = $license->ssl_is_permit
            ? $license->sslRenewalHistory()->with(['location', 'manufacturer', 'company'])
                ->orderBy('created_at')->orderBy('id')->get()
                ->filter(fn (License $period) => Gate::allows('view', $period) && Company::isCurrentUserHasAccess($period))
            : collect();

        return view('licenses.view', compact('license', 'sslRenewalHistory'))
            ->with('users_count', $users_count)
            ->with('total_seats_count', $total_seats_count)
            ->with('available_seats_count', $available_seats_count)
            ->with('checkedout_seats_count', $checkedout_seats_count)
            ->with('unreassignable_seats_count', $unreassignable_seats_count);

    }

    /**
     * Returns a view with prepopulated data for clone
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @param  int  $licenseId
     *
     * @throws AuthorizationException
     */
    public function getClone($licenseId = null): View|RedirectResponse
    {
        if (is_null($license_to_clone = License::find($licenseId))) {
            return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.does_not_exist'));
        }

        $this->authorize('clone', $license_to_clone);

        $maintained_list = [
            '' => 'Maintained',
            '1' => 'Yes',
            '0' => 'No',
        ];
        // clone the orig
        $license = clone $license_to_clone;
        $license->id = null;
        $license->serial = null;

        // Show the page
        return view('licenses/edit')
            ->with('depreciation_list', Helper::depreciationList())
            ->with('item', $license)
            ->with('maintained_list', $maintained_list);
    }

    /** Prefill a completely new validity period, preserving site/organism but not dates or uploads. */
    public function renewCreate(License $license): View|RedirectResponse
    {
        $this->authorize('create', License::class);
        $this->authorize('update', $license);
        if (! $license->ssl_is_permit || ! Company::isCurrentUserHasAccess($license)) {
            abort(404);
        }
        if ($license->sslNextPermit()->exists()) {
            return redirect()->route('licenses.show', $license)
                ->with('error', trans('admin/licenses/general.ssl_already_renewed'));
        }

        $item = new License;
        $item->ssl_is_permit = true;
        $item->name = $license->name;
        $item->category_id = $license->category_id;
        $item->company_id = $license->company_id;
        $item->location_id = $license->location_id;
        $item->manufacturer_id = $license->manufacturer_id;
        $item->ssl_notice_value = $license->ssl_notice_value;
        $item->ssl_notice_unit = $license->ssl_notice_unit;
        $item->ssl_process_status = 'pending';
        $item->seats = 1;

        return view('licenses.edit', [
            'item' => $item,
            'renewFrom' => $license,
            'depreciation_list' => Helper::depreciationList(),
            'maintained_list' => ['' => 'Maintained', '1' => 'Yes', '0' => 'No'],
        ]);
    }

    /** Atomically register a renewal and retire the previous validity period. */
    public function renewStore(Request $request, License $license): RedirectResponse
    {
        $this->authorize('create', License::class);
        $this->authorize('update', $license);
        if (! $license->ssl_is_permit || ! Company::isCurrentUserHasAccess($license)) {
            abort(404);
        }

        $this->validateSslPermitInput($request, true);
        $request->validate([
            'ssl_issued_at' => 'required|date_format:Y-m-d',
            'expiration_date' => 'required|date_format:Y-m-d|after:ssl_issued_at',
        ]);

        $targetCompany = Company::getIdForCurrentUser($request->input('company_id'));
        if ((int) $targetCompany !== (int) $license->company_id
            || (int) $request->input('location_id') !== (int) $license->location_id) {
            throw ValidationException::withMessages([
                'location_id' => trans('admin/licenses/general.ssl_renew_site_mismatch'),
            ]);
        }

        $newId = DB::transaction(function () use ($request, $license): int {
            $previous = License::whereKey($license->id)->lockForUpdate()->firstOrFail();
            if (! $previous->ssl_is_permit || $previous->sslNextPermit()->exists()) {
                throw ValidationException::withMessages([
                    'license' => trans('admin/licenses/general.ssl_already_renewed'),
                ]);
            }

            $next = new License;
            $next->ssl_is_permit = true;
            $next->ssl_previous_license_id = $previous->id;
            $next->ssl_renewal_root_id = $previous->sslRenewalRootId();
            $next->company_id = $previous->company_id;
            $next->location_id = $previous->location_id;
            $next->name = $request->input('name');
            $next->category_id = $request->input('category_id');
            $next->manufacturer_id = $request->input('manufacturer_id');
            $next->expiration_date = $request->input('expiration_date');
            $next->ssl_issued_at = $request->input('ssl_issued_at');
            $next->ssl_notice_value = $request->input('ssl_notice_value');
            $next->ssl_notice_unit = $request->input('ssl_notice_unit');
            $next->ssl_process_status = $request->input('ssl_process_status', 'pending');
            $next->notes = $request->input('notes');
            $next->serial = $request->input('serial');
            $next->license_name = $request->input('license_name');
            $next->license_email = $request->input('license_email');
            $next->supplier_id = $request->input('supplier_id');
            $next->depreciation_id = $request->input('depreciation_id');
            $next->purchase_date = $request->input('purchase_date');
            $next->purchase_cost = $request->input('purchase_cost');
            $next->purchase_order = $request->input('purchase_order');
            $next->order_number = $request->input('order_number');
            $next->termination_date = $request->input('termination_date');
            $next->maintained = $request->input('maintained', 0);
            $next->min_amt = $request->input('min_amt');
            $next->seats = 1; // required native compatibility, no checkout possible
            $next->reassignable = false;
            $next->requestable = false;
            $next->created_by = auth()->id();

            // Save the old state first: once the next row exists the old
            // vigencia is immutable. Both writes are in ONE transaction.
            $previous->ssl_process_status = 'renewed';
            if (! $previous->save()) {
                throw ValidationException::withMessages($previous->getErrors()->toArray());
            }
            if (! $next->save()) {
                throw ValidationException::withMessages($next->getErrors()->toArray());
            }

            return (int) $next->id;
        }, 3);

        return redirect()->route('licenses.show', $newId)
            ->with('success', trans('admin/licenses/general.ssl_renew_success'));
    }

    /**
     * Exports Licenses to CSV
     *
     * @author [G. Martinez]
     *
     * @since [v6.3]
     *
     * @return StreamedResponse
     *
     * @throws AuthorizationException
     */
    public function getExportLicensesCsv()
    {
        $this->authorize('view', License::class);

        $this->disableDebugbar();

        $canViewKeys = Gate::allows('viewKeys', License::class);

        $response = new StreamedResponse(function () use ($canViewKeys) {
            // Open output stream
            $handle = fopen('php://output', 'w');
            $licenses = License::with('company',
                'manufacturer',
                'category',
                'supplier',
                'adminuser',
                'assignedusers',
                'location');
            if (request()->filled('category_id')) {
                $licenses = $licenses->where('category_id', request()->input('category_id'));
            }
            $licenses = $licenses->orderBy('created_at', 'DESC');
            Company::scopeCompanyables($licenses)
                ->chunk(500, function ($licenses) use ($handle, $canViewKeys) {
                    $headers = [
                        // strtolower to prevent Excel from trying to open it as a SYLK file
                        strtolower(trans('general.id')),
                        trans('general.company'),
                        trans('general.name'),
                        trans('general.serial_number'),
                        trans('general.purchase_date'),
                        trans('general.purchase_cost'),
                        trans('general.order_number'),
                        trans('general.licenses_available'),
                        trans('admin/licenses/table.seats'),
                        trans('general.created_by'),
                        trans('general.depreciation'),
                        trans('general.updated_at'),
                        trans('admin/licenses/table.deleted_at'),
                        trans('general.email'),
                        trans('admin/hardware/form.fully_depreciated'),
                        trans('general.supplier'),
                        trans('admin/licenses/form.expiration'),
                        trans('admin/licenses/form.purchase_order'),
                        trans('admin/licenses/form.termination_date'),
                        trans('admin/licenses/form.maintained'),
                        trans('general.manufacturer'),
                        trans('general.category'),
                        trans('general.min_amt'),
                        trans('admin/licenses/form.reassignable'),
                        trans('general.notes'),
                        trans('general.created_at'),
                        trans('admin/licenses/general.ssl_location'),
                        trans('admin/licenses/general.ssl_notice_start'),
                        trans('admin/licenses/general.ssl_notice_period'),
                        trans('admin/licenses/general.ssl_notice_state'),
                        trans('admin/licenses/general.ssl_process_status'),
                        trans('admin/licenses/general.ssl_issued_at'),
                        trans('admin/licenses/general.ssl_renewal_root'),
                        trans('admin/licenses/general.ssl_previous_id'),
                    ];

                    fputcsv($handle, $headers, escape: '\\');

                    $formatter = new EscapeFormula('`');

                    foreach ($licenses as $license) {
                        // Add a new row with data
                        $values = [
                            $license->id,
                            $license->company ? $license->company->name : '',
                            $license->name,
                            $canViewKeys ? $license->serial : License::PRODUCT_KEY_MASK,
                            $license->purchase_date,
                            $license->purchase_cost,
                            $license->order_number,
                            $license->free_seat_count,
                            $license->seats,
                            ($license->adminuser ? $license->adminuser->display_name : trans('admin/reports/general.deleted_user')),
                            $license->depreciation ? $license->depreciation->name : '',
                            $license->updated_at,
                            $license->deleted_at,
                            $license->email,
                            ($license->depreciate == '1') ? trans('general.yes') : trans('general.no'),
                            ($license->supplier) ? $license->supplier->name : '',
                            $license->expiration_date,
                            $license->purchase_order,
                            $license->termination_date,
                            ($license->maintained == '1') ? trans('general.yes') : trans('general.no'),
                            $license->manufacturer ? $license->manufacturer->name : '',
                            $license->category ? $license->category->name : '',
                            $license->min_amt,
                            ($license->reassignable == '1') ? trans('general.yes') : trans('general.no'),
                            $license->notes,
                            $license->created_at,
                            $license->location?->name ?? '',
                            $license->sslNoticeStartDate()?->toDateString() ?? '',
                            $license->ssl_notice_value ? $license->ssl_notice_value.' '.$license->ssl_notice_unit : '',
                            trans('admin/licenses/general.ssl_states.'.$license->sslNoticeState()),
                            $license->ssl_process_status ?? '',
                            $license->ssl_issued_at?->toDateString() ?? '',
                            $license->ssl_is_permit ? $license->sslRenewalRootId() : '',
                            $license->ssl_previous_license_id ?? '',
                        ];

                        // CSV_ESCAPE_FORMULAS is set to false in the .env
                        if (config('app.escape_formulas') === false) {
                            fputcsv($handle, $values, escape: '\\');

                            // CSV_ESCAPE_FORMULAS is set to true or is not set in the .env
                        } else {
                            fputcsv($handle, $formatter->escapeRecord($values), escape: '\\');
                        }
                    }
                });

            // Close the output stream
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="licenses-'.date('Y-m-d-his').'.csv"',
        ]);

        return $response;
    }
    /** Common web validation for site-bound permits; legacy rows can be backfilled gradually. */
    private function validateSslPermitInput(Request $request, bool $required): void
    {
        // Check chronology only when an issuance date was supplied; in-progress
        // permits may not have been issued yet. This affects SSL fields only.
        $expirationRules = [$required ? 'required' : 'nullable', 'date_format:Y-m-d'];
        if ($request->filled('ssl_issued_at') && $request->filled('expiration_date')) {
            $expirationRules[] = 'after_or_equal:ssl_issued_at';
        }

        $request->validate([
            'location_id' => [$required ? 'required' : 'nullable', 'integer', 'exists:locations,id'],
            'expiration_date' => $expirationRules,
            'ssl_issued_at' => 'nullable|date_format:Y-m-d',
            'ssl_notice_value' => 'nullable|integer|min:1|max:3650|required_with:ssl_notice_unit',
            'ssl_notice_unit' => 'nullable|in:days,months|required_with:ssl_notice_value',
            'ssl_process_status' => 'nullable|in:pending,in_progress',
        ], [
            'expiration_date.after_or_equal' => trans('admin/licenses/general.ssl_expiration_after_issue'),
        ]);

        $companyId = Company::getIdForCurrentUser($request->input('company_id'));
        if (! License::sslLocationMatchesCompany(
            $request->filled('location_id') ? (int) $request->input('location_id') : null,
            $companyId ? (int) $companyId : null
        )) {
            throw ValidationException::withMessages([
                'location_id' => trans('admin/licenses/general.ssl_location_company_error'),
            ]);
        }
    }

}
