@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('admin/licenses/general.view') }}
  - {{ $license->name }}
  @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

{{-- Page content --}}
@section('content')
@if ($license->ssl_is_permit)
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-box>
                <h3>{{ $license->name }}</h3>
                <p><strong>{{ trans('admin/licenses/general.ssl_issued_at') }}:</strong> {{ $license->ssl_issued_at?->format('d/m/Y') ?? '—' }}</p>
                <p><strong>{{ trans('admin/licenses/general.ssl_location') }}:</strong> {{ $license->location?->name ?? '—' }}</p>
                <p><strong>{{ trans('general.company') }}:</strong> {{ $license->company?->name ?? '—' }}</p>
                <p><strong>{{ trans('admin/licenses/general.ssl_organism') }}:</strong> {{ $license->manufacturer?->name ?? '—' }}</p>
                <p><strong>{{ trans('admin/licenses/form.expiration') }}:</strong> {{ $license->expiration_date?->format('d/m/Y') ?? '—' }}</p>
                <p><strong>{{ trans('admin/licenses/general.ssl_notice_start') }}:</strong> {{ $license->sslNoticeStartDate()?->format('d/m/Y') ?? '—' }}</p>
                <p><strong>{{ trans('admin/licenses/general.ssl_notice_period') }}:</strong>
                    @if ($license->ssl_notice_value)
                        {{ $license->ssl_notice_value }} {{ trans('admin/licenses/general.ssl_'.($license->ssl_notice_unit === 'months' ? 'months' : 'days')) }}
                    @else
                        —
                    @endif
                </p>
                <p><strong>{{ trans('admin/licenses/general.ssl_renewal_root') }}:</strong> #{{ $license->sslRenewalRootId() }}</p>
                <p><strong>{{ trans('admin/licenses/general.ssl_notice_state') }}:</strong>
                    @php($sslState = $license->sslNoticeState())
                    <span class="label {{ in_array($sslState, ['due', 'expired'], true) ? 'label-danger' : ($sslState === 'in_progress' ? 'label-info' : 'label-default') }}">
                        {{ trans('admin/licenses/general.ssl_states.'.$sslState) }}
                    </span>
                </p>
                @if ($sslState === 'due')
                    <div class="alert alert-warning" role="alert">{{ trans('admin/licenses/general.ssl_due_help') }}</div>
                @elseif ($sslState === 'expired')
                    <div class="alert alert-danger" role="alert">{{ trans('admin/licenses/general.ssl_expired_help') }}</div>
                @endif
            </x-box>
            <x-box>
                <h4>{{ trans('admin/licenses/general.ssl_history_title') }} ({{ $sslRenewalHistory->count() }})</h4>
                <p class="text-muted">{{ trans('admin/licenses/general.ssl_history_description') }}</p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr>
                            <th>{{ trans('general.name') }}</th>
                            <th>{{ trans('admin/licenses/general.ssl_issued_at') }}</th>
                            <th>{{ trans('admin/licenses/form.expiration') }}</th>
                            <th>{{ trans('admin/licenses/general.ssl_location') }}</th>
                            <th>{{ trans('admin/licenses/general.ssl_organism') }}</th>
                            <th>{{ trans('admin/licenses/general.ssl_notice_state') }}</th>
                            <th>{{ trans('admin/licenses/general.ssl_documents') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($sslRenewalHistory as $period)
                                <tr class="{{ $period->id === $license->id ? 'info' : '' }}">
                                    <td><a href="{{ route('licenses.show', $period) }}">{{ $period->name }} (#{{ $period->id }})</a></td>
                                    <td>{{ $period->ssl_issued_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $period->expiration_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $period->location?->name ?? '—' }}</td>
                                    <td>{{ $period->manufacturer?->name ?? '—' }}</td>
                                    <td>{{ trans('admin/licenses/general.ssl_states.'.$period->sslNoticeState()) }}</td>
                                    <td><a href="{{ route('licenses.show', $period) }}">{{ $period->uploads()->count() }} {{ trans('admin/licenses/general.ssl_documents') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="help-block">{{ trans('admin/licenses/general.ssl_documents_help') }}</p>
            </x-box>
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.files-tab :item="$license" count="{{ $license->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $license->history()->count() }}" :model="$license"/>
                    <x-tabs.upload-tab :item="$license"/>
                </x-slot:tabnav>
                <x-slot:tabpanes>
                    <x-tabs.pane name="history">
                        <x-table.history name="licenseHistory" :model="$license" :route="route('api.licenses.history', $license)"/>
                    </x-tabs.pane>
                    <x-tabs.pane name="files">
                        <x-table.files object_type="licenses" :object="$license" />
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>
        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$license" img_path="{{ app('licenses_upload_url') }}" :qr_code_url="route('qr_code/common', ['object_type' => 'licenses', 'id' => $license->id])">
                    <x-slot:buttons>
                        @if (! $license->sslNextPermit()->exists())
                            <x-button.edit :item="$license" :route="route('licenses.edit', $license->id)"/>
                            @can('create', \App\Models\License::class)
                                @can('update', $license)
                                    <a class="btn btn-primary btn-sm hidden-print" href="{{ route('licenses.renew.create', $license) }}" title="{{ trans('admin/licenses/general.ssl_renew_button') }}">
                                        {{ trans('admin/licenses/general.ssl_renew_button') }}
                                    </a>
                                @endcan
                            @endcan
                        @endif
                    </x-slot:buttons>
                </x-info-panel>
            </x-box>
        </x-page-column>
    </x-container>
@else
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>

                    <x-tabs.nav-item
                            name="seats"
                            icon_type="checkedout"
                            label="{{ trans('general.assigned') }}"
                            count="{{ $license->assignedCount()->count() }}"
                    />

                    @can('checkout', $license)
                    <x-tabs.nav-item
                            name="available"
                            icon_type="available"
                            label="{{ trans('general.available') }}"
                            count="{{ $license->availCount()->count() }}"
                    />
                    @endcan

                    <x-tabs.files-tab :item="$license" count="{{ $license->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $license->history()->count() }}" :model="$license"/>
                    <x-tabs.upload-tab :item="$license"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>

                    <x-tabs.pane name="seats">
                        <x-slot:table_header>
                            {{ trans('general.assigned') }}
                        </x-slot:table_header>

                        @can('checkin', $license)
                        <x-slot:bulkactions>
                            <x-table.bulk-actions
                                name="licenseAssignedSeats"
                                action_route="{{ route('licenses.bulkcheckin.show') }}"
                                model_name="seat"
                            >
                                <option value="checkin">{{ trans('admin/licenses/general.checkin') }}</option>
                            </x-table.bulk-actions>
                        </x-slot:bulkactions>
                        @endcan

                        <x-table
                            name="licenseAssignedSeats"
                            fixed_right_number="1"
                            fixed_number="1"
                            api_url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'assigned']) }}"
                            :presenter="\App\Presenters\LicensePresenter::dataTableLayoutSeats()"
                            export_filename="permisologia-{{ str_slug($license->name) }}-asignados-{{ date('Y-m-d') }}"
                        />

                    </x-tabs.pane>


                    @can('checkout', $license)
                    <x-tabs.pane name="available">
                        <x-slot:table_header>
                            {{ trans('general.available') }}
                        </x-slot:table_header>

                        <x-table
                            name="licenseAvailableSeats"
                            show_search="false"
                            api_url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'available']) }}"
                            :presenter="\App\Presenters\LicensePresenter::dataTableLayoutSeats(false)"
                            export_filename="permisologia-{{ str_slug($license->name) }}-disponibles-{{ date('Y-m-d') }}"
                        />

                    </x-tabs.pane>
                    @endcan


                    <!-- start history tab pane -->
                    <x-tabs.pane name="history">
                        <x-table.history name="licenseHistory" :model="$license" :route="route('api.licenses.history', $license)"/>
                    </x-tabs.pane>
                    <!-- end history tab pane -->


                    <!-- start files tab pane -->
                    <x-tabs.pane name="files">
                        <x-table.files object_type="licenses" :object="$license" />
                    </x-tabs.pane>
                    <!-- end files tab pane -->

                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$license" img_path="{{ app('licenses_upload_url') }}" :qr_code_url="route('qr_code/common', ['object_type' => 'licenses', 'id' => $license->id])">


                    <x-slot:buttons>
                        <x-button.edit :item="$license" :route="route('licenses.edit', $license->id)"/>
                        <x-button.clone :item="$license" :route="route('clone/license', $license->id)"/>
                        <x-button.checkout permission="checkout" :item="$license" :route="route('licenses.checkout', $license->id)" />

                        @can('checkout', $license)

                            @if (($license->availCount()->count() > 0) && (!$license->isInactive()))

                                <a href="#" class="btn bg-maroon btn-sm hidden-print" data-toggle="modal" data-tooltip="true" title="{{ trans('admin/licenses/general.bulk.checkout_all.enabled_tooltip') }}" data-target="#checkoutFromAllModal">
                                    <x-icon type="checkout-all" class="fa-fw"/>
                                </a>

                            @else
                                <span data-tooltip="true" title="{{ ($license->availCount()->count() == 0) ? trans('admin/licenses/general.bulk.checkout_all.disabled_tooltip') : trans('admin/licenses/message.checkout.license_is_inactive') }}" class="btn bg-maroon btn-sm hidden-print disabled" title="{{ trans('general.checkout') }}">
                                      <x-icon type="checkout-all" class="fa-fw"/>
                                  </span>
                            @endif
                        @endcan


                        @can('checkin', $license)

                            @if (($license->seats - $license->availCount()->count()) <= 0 )
                                <span data-tooltip="true" title=" {{ trans('admin/licenses/general.bulk.checkin_all.disabled_tooltip') }}">
                                        <a href="#" class="btn btn-primary bg-purple btn-sm hidden-print disabled"><x-icon type="checkin-all" class="fa-fw"/></a>
                                    </span>
                            @else
                                <a href="#" class="btn bg-purple btn-sm hidden-print" data-toggle="modal" data-tooltip="true" data-target="#checkinFromAllModal" data-content="{{ trans('general.sure_to_delete') }} title=" {{ trans('admin/licenses/general.bulk.checkin_all.button') }} data-title=" {{ trans('admin/licenses/general.bulk.checkin_all.button') }}">
                                    <x-icon type="checkin-all" class="fa-fw"/>
                                </a>
                            @endif
                        @endcan


                        <x-button.delete :item="$license" />


                    </x-slot:buttons>


                    <x-slot:before_list>




                    </x-slot:before_list>
                </x-info-panel>
            </x-box>

        </x-page-column>
    </x-container>

@can('checkin', \App\Models\License::class)
    <x-modals.confirm-action
        modal-name="checkinFromAllModal"
        :route="route('licenses.bulkcheckin', $license->id)"
        :title="trans('general.modal_confirm_generic')"
    >
        {{ trans_choice('admin/licenses/general.bulk.checkin_all.modal', 2, ['checkedout_seats_count' => $checkedout_seats_count]) }}
    </x-modals.confirm-action>
@endcan

@can('checkout', \App\Models\License::class)
    <x-modals.confirm-action
        modal-name="checkoutFromAllModal"
        :route="route('licenses.bulkcheckout', $license->id)"
        :title="trans('general.modal_confirm_generic')"
    >
        {{ trans_choice('admin/licenses/general.bulk.checkout_all.modal', 2, ['available_seats_count' => $available_seats_count]) }}
    </x-modals.confirm-action>
@endcan

@endif
@endsection

@section('moar_scripts')
    @can('files', $license)
        <x-modals.upload-file item-type="licenses" :item-id="$license->id" />
    @endcan

    @include ('partials.bootstrap-table')
@endsection
