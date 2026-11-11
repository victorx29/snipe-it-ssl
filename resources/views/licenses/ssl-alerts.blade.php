@extends('layouts/default')

@section('title')
    {{ trans('admin/licenses/general.ssl_dashboard_title') }}
    @parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-12">
            <h3>{{ trans('admin/licenses/general.ssl_dashboard_title') }}</h3>
            <p class="text-muted">{{ trans('admin/licenses/general.ssl_dashboard_description') }}</p>
            <p>
                <a class="btn btn-default btn-sm" href="{{ route('licenses.index') }}">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    {{ trans('admin/licenses/general.ssl_dashboard_back') }}
                </a>
            </p>
        </div>
    </div>

    {{-- Every counter is computed only from records the caller can view (Gate + FMCS). --}}
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success" role="status">
                <strong>{{ trans('admin/licenses/general.ssl_dashboard_valid_count') }}: {{ $legalCounts['valid'] }}</strong>
                <a class="btn btn-success btn-sm" href="{{ route('licenses.ssl.alerts', array_merge(request()->except('page', 'legal_state'), ['legal_state' => 'valid'])) }}">
                    {{ trans('admin/licenses/general.ssl_dashboard_show_valid') }}
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        @foreach (['expired' => 'bg-red', 'due' => 'bg-yellow', 'in_progress' => 'bg-aqua', 'scheduled' => 'bg-green'] as $state => $background)
            <div class="col-lg-3 col-sm-6">
                <div class="small-box {{ $background }}">
                    <div class="inner">
                        <h3>{{ $counts[$state] }}</h3>
                        <p>{{ trans('admin/licenses/general.ssl_states.'.$state) }}</p>
                    </div>
                    <a class="small-box-footer" href="{{ route('licenses.ssl.alerts', array_merge(request()->except('page', 'state'), ['state' => $state])) }}">
                        {{ trans('admin/licenses/general.ssl_dashboard_filters') }}
                        <i class="fa-solid fa-arrow-circle-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    @if ($counts['not_configured'] > 0 || $counts['no_expiration'] > 0)
        <div class="alert alert-warning" role="status">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            {{ trans('admin/licenses/general.ssl_dashboard_configuration_notice', ['count' => $counts['not_configured'] + $counts['no_expiration']]) }}
        </div>
    @endif

    <x-box>
        <h4>{{ trans('admin/licenses/general.ssl_dashboard_filters') }}</h4>
        <form action="{{ route('licenses.ssl.alerts') }}" method="GET" class="hidden-print">
            <div class="row">
                <div class="col-lg-3 col-sm-6 form-group">
                    <label for="ssl-alert-q">{{ trans('admin/licenses/general.ssl_dashboard_search') }}</label>
                    <input type="search" class="form-control" id="ssl-alert-q" name="q" maxlength="120" value="{{ $search }}">
                </div>
                <div class="col-lg-3 col-sm-6 form-group">
                    <label for="ssl-alert-company">{{ trans('general.company') }}</label>
                    <select class="form-control" id="ssl-alert-company" name="company_id">
                        <option value="">{{ trans('admin/licenses/general.ssl_dashboard_any_company') }}</option>
                        @foreach ($companies as $id => $name)
                            <option value="{{ $id }}" @selected($selectedCompany === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-sm-6 form-group">
                    <label for="ssl-alert-location">{{ trans('admin/licenses/general.ssl_location') }}</label>
                    <select class="form-control" id="ssl-alert-location" name="location_id">
                        <option value="">{{ trans('admin/licenses/general.ssl_dashboard_any_location') }}</option>
                        @foreach ($locations as $id => $name)
                            <option value="{{ $id }}" @selected($selectedLocation === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-sm-6 form-group">
                    <label for="ssl-alert-state">{{ trans('admin/licenses/general.ssl_notice_state') }}</label>
                    <select class="form-control" id="ssl-alert-state" name="state">
                        <option value="all">{{ trans('admin/licenses/general.ssl_dashboard_all') }}</option>
                        @foreach (['expired', 'due', 'in_progress', 'scheduled', 'not_configured', 'no_expiration', 'not_issued', 'cancelled'] as $state)
                            <option value="{{ $state }}" @selected($selectedState === $state)>{{ trans('admin/licenses/general.ssl_states.'.$state) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="ssl-alert-legal">{{ trans('admin/licenses/general.ssl_legal_status') }}</label>
                    <select class="form-control" id="ssl-alert-legal" name="legal_state">
                        <option value="all">{{ trans('admin/licenses/general.ssl_dashboard_any_legal') }}</option>
                        @foreach (['valid', 'expired', 'cancelled', 'not_issued', 'unverified'] as $legalState)
                            <option value="{{ $legalState }}" @selected($selectedLegalState === $legalState)>{{ trans('admin/licenses/general.ssl_legal_states.'.$legalState) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ trans('admin/licenses/general.ssl_dashboard_apply') }}</button>
            <a href="{{ route('licenses.ssl.alerts') }}" class="btn btn-default btn-sm">{{ trans('admin/licenses/general.ssl_dashboard_clear') }}</a>
        </form>
    </x-box>

    <x-box>
        <h4>{{ trans('admin/licenses/general.ssl_dashboard_total') }}: {{ $totalCurrent }}</h4>
        <p class="text-muted">{{ trans('admin/licenses/general.ssl_dashboard_counts_help') }}</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>{{ trans('admin/licenses/general.permit_name') }}</th>
                        <th>{{ trans('general.company') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_location') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_organism') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_issued_at') }}</th>
                        <th>{{ trans('admin/licenses/form.expiration') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_notice_start') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_legal_status') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_notice_state') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_process_status') }}</th>
                        <th>{{ trans('admin/licenses/general.ssl_documents') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $entry)
                        @php
                            $permit = $entry['permit'];
                            $state = $entry['state'];
                            $labelStyle = match ($state) {
                                'expired' => 'label-danger',
                                'due' => 'label-warning',
                                'in_progress' => 'label-info',
                                'scheduled' => 'label-success',
                                default => 'label-default',
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('licenses.show', $permit) }}">{{ $permit->name }}</a></td>
                            <td>{{ $permit->company?->name ?? '—' }}</td>
                            <td>{{ $permit->location?->name ?? '—' }}</td>
                            <td>{{ $permit->manufacturer?->name ?? '—' }}</td>
                            <td>{{ $permit->ssl_issued_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $permit->expiration_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $entry['notice_date']?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="label {{ $permit->sslLegalStatus() === 'valid' ? 'label-success' : (in_array($permit->sslLegalStatus(), ['expired', 'cancelled'], true) ? 'label-danger' : 'label-default') }}">{{ trans('admin/licenses/general.ssl_legal_states.'.$permit->sslLegalStatus()) }}</span></td>
                            <td><span class="label {{ $labelStyle }}">{{ trans('admin/licenses/general.ssl_states.'.$state) }}</span></td>
                            <td>{{ trans('admin/licenses/general.ssl_process_'.($permit->ssl_process_status ?: 'pending')) }}</td>
                            <td><a href="{{ route('licenses.show', $permit) }}">{{ trans('admin/licenses/general.ssl_dashboard_action') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">{{ trans('admin/licenses/general.ssl_dashboard_no_matches') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="text-center">{{ $rows->links() }}</div>
        @endif
    </x-box>

    <p class="text-muted"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> {{ trans('admin/licenses/general.ssl_dashboard_manual') }}</p>
</x-container>
@endsection
