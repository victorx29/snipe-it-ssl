@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/users/general.view_user', ['name' => $user->display_name]) }}
@parent
@stop

@section('header_right')
    <x-button.info-panel-toggle hide-on-xs/>
@endsection

{{-- Page content --}}
@section('content')

    @php
        // These are eagerly loaded by UsersController only when authorized.
        $sslCanViewAccessories = auth()->user()->can('view', \App\Models\Accessory::class);
        $sslUniformes = $sslCanViewAccessories ? $user->sslUniformes : collect();
        $sslCapacitaciones = $sslCanViewAccessories ? $user->sslCapacitaciones : collect();
    @endphp

    <x-container columns="2">

        @if ($user->deleted_at!='')
            <div class="col-md-12">
                <x-callout type="warning" icon="warning" live="assertive">
                    {{ trans('admin/users/message.user_deleted_warning') }}
                </x-callout>
            </div>
        @endif

        <x-page-column class="col-md-9 main-panel">

            {{-- SSL: identification header sourced exclusively from existing User fields. --}}
            <div class="well well-sm hidden-print" role="group" aria-label="{{ trans('admin/users/general.ssl_worker_summary') }}">
                <div class="row" style="margin: 0;">
                    <div class="col-sm-5">
                        <strong><i class="fa-solid fa-id-card fa-fw" aria-hidden="true"></i> {{ $user->display_name }}</strong>
                        <div class="text-muted">{{ trans('admin/users/table.employee_num') }}: {{ $user->employee_num ?: trans('general.na') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <strong>{{ trans('admin/users/table.job') }}:</strong> {{ $user->jobtitle ?: trans('general.na') }}
                        <div class="text-muted">{{ trans('general.department') }}: {{ $user->department?->name ?? trans('general.na') }}</div>
                    </div>
                    <div class="col-sm-3">
                        <strong>{{ trans('general.company') }}:</strong>
                        {{ $user->companies->pluck('name')->join(', ') ?: trans('general.na') }}
                    </div>
                </div>
            </div>

            {{-- SSL 5.1: constancia institucional separada de la impresión nativa. --}}
            @if (auth()->user()->can('view', \App\Models\Asset::class) || $sslCanViewAccessories)
                <div class="text-right hidden-print" style="margin-bottom: 12px;">
                    <a class="btn btn-primary btn-sm"
                       href="{{ route('users.print', $user->id) }}?ssl_document=activos_uniformes"
                       target="_blank" rel="noopener"
                       title="{{ trans('admin/users/general.ssl_assets_uniforms_receipt') }}">
                        <i class="fa-solid fa-file-signature fa-fw" aria-hidden="true"></i>
                        {{ trans('admin/users/general.ssl_assets_uniforms_receipt') }}
                    </a>
                </div>
            @endif

            {{-- SSL 5.2: constancia independiente de dotación de EPP. --}}
            @can('view', \App\Models\Consumable::class)
                <div class="text-right hidden-print" style="margin-bottom: 12px;">
                    <a class="btn btn-default btn-sm"
                       href="{{ route('users.print', $user->id) }}?ssl_document=epp"
                       target="_blank" rel="noopener"
                       title="{{ trans('admin/users/general.ssl_epp_receipt') }}">
                        <i class="fa-solid fa-helmet-safety fa-fw" aria-hidden="true"></i>
                        {{ trans('admin/users/general.ssl_epp_receipt') }}
                    </a>
                </div>
            @endcan

            {{-- SSL 5.3: acceso al centro de constancias de activos por sede,
                 mientras se incorpora el acceso directo a Ubicaciones. --}}
            @if (auth()->user()->can('view', \App\Models\Location::class)
                 && auth()->user()->can('view', \App\Models\Asset::class))
                <div class="text-right hidden-print" style="margin-bottom: 12px;">
                    <a class="btn btn-default btn-sm"
                       href="{{ route('ssl.locations.receipts.index') }}"
                       title="Constancias de activos entregados por sede">
                        <i class="fa-solid fa-building fa-fw" aria-hidden="true"></i>
                        Constancias de activos por sede
                    </a>
                </div>
            @endif

            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.nav-item name="assets" icon="fa-solid fa-shield-halved" :label="trans('general.assets')" :count="$user->assets()->AssetsForShow()->count()" />
                    <x-tabs.nav-item name="licenses" icon="fa-solid fa-file-signature" :label="trans('general.licenses')" :count="$user->licenses()->count()" />
                    @if ($sslCanViewAccessories)
                        <x-tabs.nav-item name="ssl-uniformes" icon="fa-solid fa-shirt" :label="trans('admin/users/general.ssl_uniforms')" :count="$sslUniformes->count()" />
                        <x-tabs.nav-item name="ssl-capacitaciones" icon="fa-solid fa-graduation-cap" :label="trans('admin/users/general.ssl_trainings')" :count="$sslCapacitaciones->count()" />
                    @endif
                    <x-tabs.nav-item name="accessories" icon="fa-solid fa-list-check" :label="trans('admin/users/general.ssl_shared_assignments')" :count="$user->accessories()->count()" />
                    <x-tabs.nav-item name="consumables" icon="fa-solid fa-helmet-safety" :label="trans('general.consumables')" :count="$user->consumables()->count()" />
                    <x-tabs.maintenance-tab count="{{ $user->assignedMaintenances()->count() }}"/>
                    <x-tabs.files-tab :item="$user" count="{{ $user->uploads()->count() }}"/>
                    <x-tabs.eula-tab count="{{ $user->eulas()->count() }}"/>
                    <x-tabs.location-tab count="{{ $user->managedLocations()->count() }}"/>
                    <x-tabs.user-tab count="{{ $user->managesUsers()->count() }}" name="managed-users" icon_type="manager" :label="trans('admin/users/table.managed_users')"/>
                    <x-tabs.history-tab count="{{ $user->history->count() }}" :model="$user"/>
                    <x-tabs.upload-tab :item="$user"/>
                </x-slot:tabnav>


                <x-slot:tabpanes>

                    <!-- start details tab content -->
                    <x-tabs.pane name="details">

                        <!-- this just adds a little top space -->
                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>

                        <!-- well column -->
                        <x-page-column class="col-md-4">
                            <x-well>
                                <x-icon type="calendar" class="fa-fw"/>
                                <strong>{{ trans('general.last_login') }}</strong>
                                @if ($user->last_login != '')
                                    {{ Helper::getFormattedDateObject($user->last_login, 'datetime', false) }}
                                    <span class="text-muted">{{ Carbon::parse($user->last_login)->diffForHumans(['parts' => 2]) }}</span>
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </x-well>
                        </x-page-column>
                        <!--  ./ well column -->

                        <!-- well column -->
                        <x-page-column class="col-md-4">
                            <x-well>
                                <x-icon type="start_date" class="fa-fw"/>
                                <strong>{{ trans('general.start_date') }}</strong>
                                @if ($user->start_date != '')
                                    {{ Helper::getFormattedDateObject($user->start_date, 'date', false) }}
                                    <span class="text-muted">{{ Carbon::parse($user->start_date)->diffForHumans(['parts' => 2]) }}</span>
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </x-well>
                        </x-page-column>
                        <!-- ./ well column -->

                        <!-- well column -->
                        <x-page-column class="col-md-4">
                            <x-well>
                                <x-icon type="end_date" class="fa-fw {{ (($user->end_date!='' && $user->end_date < Carbon::now()) ? ' text-danger' : '') }}"/>
                                <strong>{{ trans('general.end_date') }}</strong>
                                @if ($user->end_date != '')
                                    {{ Helper::getFormattedDateObject($user->end_date, 'date', false) }}
                                    <span class="text-muted">{{ Carbon::parse($user->end_date)->diffForHumans(['parts' => 2]) }}</span>
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </x-well>

                        </x-page-column>
                        <!-- ./ well column -->

                        <!-- set clearfix for responsive design -->
                        <div class="clearfix"></div>

                        <!-- definition list column -->
                        <x-page-column class="col-md-8 col-sm-12">

                            <!-- definition list content -->
                            <x-page-data>

                                <x-data-row :label="trans('general.name')" copy_what="name">
                                    {{ $user->full_name }}
                                </x-data-row>

                                <x-data-row :label="trans('admin/users/table.display_name')" copy_what="display_name">
                                    {{ $user->getRawOriginal('display_name') }}
                                </x-data-row>

                                <x-data-row :label="trans('general.username')" copy_what="username">
                                    @if ($user->isSuperUser())
                                        <span class="label label-danger label-normal" data-tooltip="true" title="{{ trans('general.superuser_tooltip') }}"><x-icon type="superadmin" style="padding-right: 5px;"/>{{ $user->username }}</span>
                                    @elseif ($user->hasAccess('admin'))
                                        <span class="label label-warning label-normal" data-tooltip="true" title="{{ trans('general.admin_tooltip') }}"><x-icon type="superadmin" style="padding-right: 5px;"/>{{ $user->username }}</span>
                                    @else
                                        {{ $user->username }}
                                    @endif
                                </x-data-row>

                                <x-data-row :label="trans('admin/users/table.employee_num')" copy_what="employee_num">
                                    {{ $user->employee_num }}
                                </x-data-row>

                                <x-data-row :label="trans('admin/users/table.job')" copy_what="jobtitle">
                                    {{ $user->jobtitle }}
                                </x-data-row>

                                <x-data-row :label="trans('general.groups')" copy_what="groups">
                                    @if ($user->groups->count() > 0)
                                        @foreach ($user->groups as $group)
                                            {!! $group->present()->formattedNameLink() !!}
                                        @endforeach
                                    @endif

                                    @if ($user->hasIndividualPermissions())
                                        <span class="text-warning"><x-icon type="warning"/> {{ trans('admin/users/general.individual_override') }}</span>
                                    @endif
                                </x-data-row>

                                <x-data-row :label="trans('general.permissions')">
                                    @if ($user->isSuperUser())
                                        <span class="label label-danger label-normal" data-tooltip="true" title="{{ trans('general.superuser_tooltip') }}">
                                            <x-icon type="superadmin" style="padding-right: 5px;"/>{{ trans('general.superuser') }}
                                        </span>
                                    @elseif ($user->hasAccess('admin'))
                                        <span class="label label-warning label-normal" data-tooltip="true" title="{{ trans('general.admin_tooltip') }}">
                                            <x-icon type="superadmin" style="padding-right: 5px;"/>{{ trans('general.admin_user') }}
                                        </span>
                                    @elseif (!empty($effectivePermissionsBySection))
                                        @foreach ($effectivePermissionsBySection as $section => $permissions)
                                            @foreach ($permissions as $permission)
                                                @if (($permission['status'] ?? 'allowed') === 'denied')
                                                    <span class="label label-danger denied-permission" data-tooltip="true" title="{{ $permission['source_label'] }}"><x-icon type="x" class="fa-fw"/> {{ $permission['permission'] }}</span>
                                                @else
                                                    <span class="label label-success" data-tooltip="true" title="{{ $permission['source_label'] }}"><x-icon type="checkmark" class="fa-fw"/> {{ $permission['permission'] }}</span>
                                                @endif
                                            @endforeach

                                        @endforeach
                                    @endif
                                </x-data-row>

                            @if (($user->email!='') && ($user->activated=='1')  && ($user->getAssignedItemsWithPendingAcceptance()->count() > 0))

                                    <x-data-row :label="trans_choice('admin/users/general.unaccepted_items', $user->getAssignedItemsWithPendingAcceptance()->count())">
                                        <form action="{{ route('users.acceptance_reminder', $user) }}" method="POST" class="form-inline" style="display: inline;">
                                            {{ csrf_field() }}
                                            <button class="btn btn-warning btn-sm" type="submit">
                                                {{ trans('admin/users/general.send_acceptance_reminder') }}
                                            </button>
                                        </form>
                                    </x-data-row>
                                @endif

                            </x-page-data>

                            <!-- ./ definition list column -->

                        </x-page-column>
                        <!-- ./ definition list content -->

                        <!-- begin side stats well column-->
                        <x-page-column class="col-md-4 col-sm-12">


                            @if ($user->getUserTotalCost()->total_user_cost > 0)
                                <x-well class="well-sm">

                                    <div class="well-display">

                                        <x-data-row icon_type="asset" label="{{ trans('general.assets') }}" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->asset_cost) }}
                                        </x-data-row>

                                        <x-data-row icon_type="licenses" label="{{ trans('general.licenses') }}" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->license_cost)}}
                                        </x-data-row>

                                        <x-data-row icon_type="accessories" label="{{ trans('general.accessories') }}" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->accessory_cost)}}
                                        </x-data-row>

                                        <x-data-row icon_type="consumables" label="{{ trans('general.consumables') }}" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->consumable_cost) }}
                                        </x-data-row>

                                        {{-- Sum across complete + active maintenances tied
                                             to this user — used to surface "which users
                                             cost the most maintenance over time" as a glance.
                                             The current-active count lives on the tab badge
                                             above so we don't duplicate it here. --}}
                                        <x-data-row icon_type="maintenances" :label="trans('general.maintenance_cost')" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->maintenance_cost) }}
                                        </x-data-row>

                                        <x-data-row icon_type="cost" label=" {{ trans('admin/users/table.total_assets_cost') }}" align="right">
                                            {{ Helper::formatCurrencyOutput($user->getUserTotalCost()->total_user_cost) }}
                                        </x-data-row>

                                    </div>

                                </x-well>
                            @endif


                            <x-well class="well-sm" style="padding-left: 15px;">

                                @if($user->activated == '1')
                                    <x-icon type="checkmark" class="fa-fw text-success"/>
                                    {{ trans('general.login_enabled') }}

                                    <br>
                                    @if ($user->two_factor_active())
                                        <x-icon type="checkmark" class="fa-fw text-success"/>
                                    @else
                                        <x-icon type="x" class="fa-fw text-danger"/>
                                    @endif
                                    {{ trans('admin/users/general.two_factor_active') }}

                                    <br>
                                    @if ($user->two_factor_active_and_enrolled())
                                        <x-icon type="checkmark" class="fa-fw text-success"/>
                                    @else
                                        <x-icon type="x" class="fa-fw text-danger"/>
                                    @endif
                                    {{ trans('admin/users/general.two_factor_enrolled') }}

                                @else
                                    <x-icon type="x" class="fa-fw text-danger"/>
                                    {{ trans('general.login_enabled') }}
                                @endif

                                <br>

                                @if($user->vip == '1')
                                    <x-icon type="vip" class="fa-fw text-warning"/>
                                    {{ trans('admin/users/general.vip_label') }}
                                    <br>
                                @endif

                                <x-icon type="api-key" class="fa-fw"/>
                                {{ $user->tokens()->count() }} API Tokens
                                <br>

                                @if($user->remote == '1')
                                    <x-icon type="remote" class="fa-fw"/>
                                    {{ trans('admin/users/general.remote') }}
                                    <br>
                                @endif

                                @if($user->ldap_import == '1')
                                    <x-icon type="ldap" class="fa-fw"/>
                                    {{ trans('admin/settings/general.ldap_enabled') }}
                                    <br>
                                @endif

                                @if ($user->autoassign_licenses == '1')
                                    <x-icon type="checkmark" class="fa-fw text-success"/>
                                @else
                                    <x-icon type="x" class="fa-fw text-danger"/>
                                @endif
                                {{ trans('general.autoassign_licenses') }}
                                <br>

                            </x-well>


                                <!-- Impersonation button -->
                                @if (Auth::check() && Auth::user()->mayImpersonate($user))
                                    <button type="button" class="btn btn-danger hidden-print btn-social btn-block" data-toggle="modal" data-target="#confirmImpersonateModal" data-tooltip="true" data-title="{{ trans('admin/users/general.impersonate_user', ['name' => $user->display_name]) }}">
                                        <x-icon type="impersonate" class="fa-fw" style="font-size: 17px;"/>
                                        {{ trans('general.impersonate') }}
                                        <span class="sr-only">{{ trans('admin/users/general.impersonate_user', ['name' => $user->display_name]) }}</span>
                                    </button>
                                @endif



                                @if (auth()->user()->isSuperUser() && $user->twoFactorResettable())
                                    <!-- 2FA reset -->
                                    <button type="button" class="btn btn-theme hidden-print btn-social btn-block" data-toggle="modal" data-target="#confirmTwoFactorResetModal" style="margin-right: 10px; margin-top: 10px;">
                                        <x-icon type="mobile" class="fa-fw"/>
                                        {{ trans('admin/settings/general.two_factor_reset') }}
                                    </button>
                                    <br>
                                    <p class="help-block" style="line-height: 1.6;">
                                        {{ trans('admin/settings/general.two_factor_reset_help') }}
                                    </p>
                                @endif



                        </x-page-column>
                        <!-- end side stats well column-->

                    </x-tabs.pane>

                    <x-tabs.pane name="licenses" :count="$user->licenses()->count()">

                        <x-slot:table_header>{{ trans('general.licenses') }}</x-slot:table_header>

                        @can('checkin', \App\Models\License::class)
                        <x-slot:bulkactions>
                            <x-table.bulk-actions
                                name="userLicenseTable"
                                :action_route="route('licenses.bulkcheckin.show')"
                                model_name="license_seats"
                                :actions="[
                                    'checkin' => ['label' => trans('general.checkin')],
                                ]"
                            />
                        </x-slot:bulkactions>
                        @endcan

                        @can('view', \App\Models\License::class)
                        <x-table
                            name="userLicenseTable"
                            show_column_search="false"
                            show_footer="true"
                            buttons="licenseButtons"
                            api_url="{{ route('api.users.licenselist', ['user' => $user->id]) }}"
                            :presenter="\App\Presenters\UserPresenter::licensesDataTableLayout()"
                            export_filename="export-license-{{ str_slug($user->username) }}-{{ date('Y-m-d') }}"
                        />
                        @endcan

                    </x-tabs.pane>

                    <x-tabs.pane name="assets" :count="$user->assets()->AssetsForShow()->count()">
                        <x-table.assets name="userAssets" :route="route('api.assets.index',['assigned_to' => e($user->id), 'assigned_type' => 'App\Models\User'])"/>
                    </x-tabs.pane>

                    {{-- SSL: these two lists are scoped at the Eloquent relationship, not by JS. --}}
                    @if ($sslCanViewAccessories)
                        <x-tabs.pane name="ssl-uniformes" :count="$sslUniformes->count()">
                            <x-slot:table_header>{{ trans('admin/users/general.ssl_uniforms') }}</x-slot:table_header>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <caption class="sr-only">{{ trans('admin/users/general.ssl_uniforms') }}</caption>
                                    <thead><tr>
                                        <th scope="col">{{ trans('general.name') }}</th>
                                        <th scope="col">{{ trans('general.category') }}</th>
                                        <th scope="col">{{ trans('admin/users/general.ssl_assignment_date') }}</th>
                                        <th scope="col">{{ trans('general.notes') }}</th>
                                    </tr></thead>
                                    <tbody>
                                    @forelse ($sslUniformes as $sslUniforme)
                                        <tr>
                                            <td>
                                                @can('view', $sslUniforme)
                                                    <a href="{{ route('accessories.show', $sslUniforme->id) }}">{{ $sslUniforme->name }}</a>
                                                @else
                                                    {{ $sslUniforme->name }}
                                                @endcan
                                            </td>
                                            <td>{{ $sslUniforme->category?->name ?? trans('general.invalid_category') }}</td>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($sslUniforme->pivot->created_at, 'datetime', false) }}</td>
                                            <td>{{ $sslUniforme->pivot->note }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">{{ trans('admin/users/general.ssl_no_uniforms') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted">{{ trans('admin/users/general.ssl_actions_in_shared') }}</p>
                        </x-tabs.pane>

                        <x-tabs.pane name="ssl-capacitaciones" :count="$sslCapacitaciones->count()">
                            <x-slot:table_header>{{ trans('admin/users/general.ssl_trainings') }}</x-slot:table_header>
                            <x-callout type="info" icon="tip" live="polite">
                                Las referencias anteriores no acreditan asistencia. Las sesiones de SSL, Gestión Humana y Tecnología muestran horas únicamente cuando la asistencia está confirmada.
                            </x-callout>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <caption class="sr-only">{{ trans('admin/users/general.ssl_trainings') }}</caption>
                                    <thead><tr>
                                        <th scope="col">{{ trans('general.name') }}</th>
                                        <th scope="col">{{ trans('admin/users/general.ssl_training_area') }}</th>
                                        <th scope="col">Fecha de inscripción</th>
                                        <th scope="col">Asistencia</th>
                                        <th scope="col">Minutos acreditados</th>
                                        <th scope="col">{{ trans('general.notes') }}</th>
                                    </tr></thead>
                                    <tbody>
                                    @forelse ($sslCapacitaciones as $sslCapacitacion)
                                        <tr>
                                            <td>
                                                @can('view', $sslCapacitacion)
                                                    <a href="{{ $sslCapacitacion->ssl_training_starts_at ? route('ssl.trainings.show', $sslCapacitacion->id) : route('accessories.show', $sslCapacitacion->id) }}">{{ $sslCapacitacion->name }}</a>
                                                @else
                                                    {{ $sslCapacitacion->name }}
                                                @endcan
                                            </td>
                                            <td>{{ \App\Models\User::sslTrainingAreaLabel($sslCapacitacion->category?->name) }}</td>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($sslCapacitacion->pivot->created_at, 'datetime', false) }}</td>
                                            <td>
                                                @switch($sslCapacitacion->pivot->ssl_attendance_status)
                                                    @case('attended') Asistió @break
                                                    @case('registered') Inscrito @break
                                                    @case('absent') Ausente @break
                                                    @case('excused') Justificado @break
                                                    @default Referencia anterior (no confirma asistencia)
                                                @endswitch
                                            </td>
                                            <td>{{ $sslCapacitacion->pivot->ssl_attendance_status === 'attended' && $sslCapacitacion->ssl_training_status === 'completed' ? $sslCapacitacion->pivot->ssl_attended_minutes : '—' }}</td>
                                            <td>{{ $sslCapacitacion->pivot->note }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">{{ trans('admin/users/general.ssl_no_trainings') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-tabs.pane>
                    @endif

                    <x-tabs.pane name="accessories" :count="$user->accessories()->count()">
                        <x-slot:table_header>
                            {{ trans('admin/users/general.ssl_shared_assignments') }}
                        </x-slot:table_header>

                        <p class="text-muted">{{ trans('admin/users/general.ssl_shared_assignments_note') }}</p>
                        @can('view', \App\Models\Accessory::class)
                        <x-table
                            name="userAccessoryTable"
                            show_column_search="false"
                            show_footer="true"
                            buttons="accessoryButtons"
                            api_url="{{ route('api.users.accessorieslist', ['user' => $user->id]) }}"
                            :presenter="\App\Presenters\UserPresenter::accessoriesDataTableLayout()"
                            export_filename="export-accessory-{{ str_slug($user->username) }}-{{ date('Y-m-d') }}"
                        />
                        @endcan

                    </x-tabs.pane>

                    <x-tabs.pane name="consumables" :count="$user->consumables()->count()">
                        @can('view', \App\Models\Consumable::class)
                        <x-table
                            name="userConsumableTable"
                            show_column_search="false"
                            show_footer="true"
                            buttons="consumableButtons"
                            api_url="{{ route('api.users.consumableslist', ['user' => $user->id]) }}"
                            :presenter="\App\Presenters\UserPresenter::consumablesDataTableLayout()"
                            export_filename="export-consumable-{{ str_slug($user->username) }}-{{ date('Y-m-d') }}"
                        />
                        @endcan

                    </x-tabs.pane>

                    {{-- Maintenances tab: every maintenance whose underlying
                         asset was checked out to this user. Shows open and
                         closed; the tab badge only counts the open ones
                         (see assignedMaintenances()->active() above). --}}
                    <x-tabs.pane name="maintenances" :count="$user->assignedMaintenances()->count()">
                        <x-table.maintenances name="userMaintenances"
                            :route="route('api.maintenances.index', ['checked_out_to_id' => $user->id, 'checked_out_to_type' => \App\Models\User::class])"
                            export_filename="export-maintenances-{{ str_slug($user->username) }}-{{ date('Y-m-d') }}"
                        />
                    </x-tabs.pane>

                    <x-tabs.pane name="managed-users" :count="$user->managesUsers()->count()">
                        @include('partials.users-bulk-actions')

                        <table
                            data-columns="{{ \App\Presenters\UserPresenter::dataTableLayout() }}"
                            data-cookie-id-table="managedUsersTable"
                            data-id-table="managedUsersTable"
                            data-toolbar="#usersBulkEditToolbar"
                            data-bulk-button-id="#bulkUserEditButton"
                            data-bulk-form-id="#usersBulkForm"
                            data-side-pagination="server"
                            id="managedUsersTable"
                            data-buttons="userButtons"
                            class="table table-striped snipe-table"
                            data-url="{{ route('api.users.index', ['manager_id' => $user->id]) }}"
                            data-export-options='{
              "fileName": "export-users-{{ date('Y-m-d') }}",
              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
              }'>
                        </table>
                    </x-tabs.pane>


                    <x-tabs.pane name="files" :count="$user->uploads()->count()">
                        <x-table.files object_type="users" :object="$user"/>
                    </x-tabs.pane>

                    <x-tabs.pane name="eulas" :count="$user->eulas()->count()">
                        <x-slot:table_header>
                            {{ trans('general.eula') }}
                        </x-slot:table_header>

                        <table
                            data-toolbar="#userEULAToolbar"
                            data-cookie-id-table="userEULATable"
                            data-id-table="userEULATable"
                            id="userEULATable"
                            data-side-pagination="client"
                            data-show-footer="true"
                            data-show-refresh="false"
                            data-sort-order="asc"
                            data-sort-name="name"
                            class="table table-striped snipe-table table-hover"
                            data-url="{{ route('api.user.eulas', $user) }}"
                            data-export-options='{
                    "fileName": "export-eula-{{ str_slug($user->username) }}-{{ date('Y-m-d') }}",
                    "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","delete","purchasecost", "icon"]
                    }'>
                            <thead>
                                <tr>
                                    <th scope="col" data-visible="true" data-field="icon" style="width: 40px;" class="hidden-xs" data-formatter="iconFormatter">{{ trans('admin/hardware/table.icon') }}</th>
                                    <th scope="col" data-visible="true" data-field="item.name">{{ trans('general.item') }}</th>
                                    <th scope="col" data-visible="true" data-field="created_at" data-sortable="true" data-formatter="dateDisplayFormatter">{{ trans('general.accepted_date') }}</th>
                                    <th scope="col" data-field="note">{{ trans('general.notes') }}</th>
                                    <th scope="col" data-field="url" data-formatter="fileDownloadButtonsFormatter">{{ trans('general.download') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </x-tabs.pane>


                    <!-- start locations tab pane -->
                    <x-tabs.pane name="locations">

                        <x-slot:table_header>
                            {{ trans('general.locations') }}
                        </x-slot:table_header>

                        <x-table
                            name="userLocations_{{ $user->id }}"
                            api_url="{{ route('api.locations.index', ['manager_id' => $user->id]) }}"
                            :presenter="\App\Presenters\LocationPresenter::dataTableLayout()"
                            export_filename="export-history-{{ str_slug($user->name) }}-{{ date('Y-m-d') }}"
                        />

                    </x-tabs.pane>


                    <!-- start history tab pane -->
                    <x-tabs.pane name="history">
                        <x-table.history name="userHistory" :model="$user" :route="route('api.users.history', $user)" :hide_fields="['order_number']"/>
                    </x-tabs.pane>
                    <!-- end history tab pane -->
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$user" img_path="{{ app('users_upload_url') }}">
                    <x-slot:buttons>
                        <x-button.edit :item="$user" :route="route('users.edit', $user->id)"/>
                        <x-button.clone :item="$user" :route="route('users.clone.show', $user)"/>
                        <x-button.restore :item="$user" :route="route('users.restore.store',  $user)"/>


                    @if($user->allAssignedCount() != '0')
                        <a href="{{ route('users.print', $user->id) }}" class="btn btn-sm btn-theme hidden-print" target="_blank" rel="noopener" data-tooltip="true" data-title="{{ trans('admin/users/general.print_assigned') }}">
                            <x-icon type="print" class="fa-fw"/>
                        </a>
                        @endif

                        @can('checkout', \App\Models\Asset::class)
                            @if (($user->assets()->whereNull('deleted_at')->count() + $user->accessories()->count() + $user->licenses()->count()) > 0)
                                <a href="{{ route('users.transfer.show', $user) }}" class="btn btn-sm btn-theme hidden-print" data-tooltip="true" data-title="{{ trans('admin/users/general.transfer.button_tooltip') }}">
                                    <x-icon type="transfer" class="fa-fw"/>
                                </a>
                            @endif
                        @endcan


                        @if(!empty($user->email) && ($user->allAssignedCount() != '0'))
                            <form class="form-inline" style="display: inline" action="{{ route('users.email',['userId'=> $user->id]) }}" method="POST">
                                {{ csrf_field() }}
                                <button class="btn btn-sm btn-theme hidden-print" rel="noopener" data-tooltip="true" data-title="{{ trans('admin/users/general.email_assigned') }}">
                                    <x-icon type="email" class="fa-fw"/>
                                </button>
                            </form>
                        @endif

                        @if (!empty($user->email) && ($user->activated=='1'))
                            <form action="{{ route('users.password',['userId'=> $user->id]) }}" method="POST" class="form-inline" style="display: inline">
                                {{ csrf_field() }}
                                <button class="btn btn-sm btn-primary hidden-print" data-tooltip="true" data-title="{{ trans('button.send_password_link') }}">
                                    <x-icon type="password" class="fa-fw"/>
                                </button>
                            </form>
                        @else
                            <button class="btn btn-sm btn-primary hidden-print" rel="noopener" disabled title="{{ trans('admin/users/message.user_has_no_email') }}">
                                <x-icon class="fa-solid fa-key"/>
                            </button>
                        @endif

                        <x-button.delete :item="$user"/>

                        @can('delete', $user)
                            <form action="{{ route('users/bulkedit') }}" method="POST" class="form-inline pull-right" style="display: inline; padding-right: 5px;">
                                <!-- CSRF Token -->
                                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                                <input type="hidden" name="bulk_actions" value="delete"/>
                                <input type="hidden" name="ids[{{ $user->id }}]" value="{{ $user->id }}"/>
                                <button class="btn btn-sm btn-danger hidden-print pull-right" style="margin-right: 3px;" data-tooltip="true" data-title="{{ trans('tooltips.checkin_all.user') }}">
                                    <x-icon type="checkin-and-delete" class="fa-fw"/>
                                </button>
                            </form>
                        @endcan
                    </x-slot:buttons>
                </x-info-panel>
            </x-box>

        </x-page-column>
    </x-container>

    @if (Auth::check() && Auth::user()->mayImpersonate($user))
        @include('users.impersonate-confirm-modal')
    @endif

    @if (auth()->user()->isSuperUser() && $user->twoFactorResettable())
        <x-modals.confirm-action
            modal-name="confirmTwoFactorResetModal"
            :route="route('users.two_factor_reset', $user->id)"
            :title="trans('admin/settings/general.two_factor_reset')"
        >
            {{ trans('admin/settings/general.two_factor_reset_confirm', ['name' => $user->display_name]) }}
        </x-modals.confirm-action>
    @endif

@endsection


@section('moar_scripts')
    @can('files', $user)
        <x-modals.upload-file item-type="users" :item-id="$user->id" />
    @endcan

    @include ('partials.bootstrap-table', ['simple_view' => true])
<script nonce="{{ csrf_token() }}">
$(function () {

    $("#optional_info").on("click",function(){
        $('#optional_details').fadeToggle(100);
        $('#optional_info_icon').toggleClass('fa-caret-right fa-caret-down');
        var optional_info_open = $('#optional_info_icon').hasClass('fa-caret-down');
        document.cookie = "optional_info_open="+optional_info_open+'; path=/';
    });

});
</script>

@endsection
