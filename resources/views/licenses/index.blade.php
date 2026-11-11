@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/licenses/general.software_licenses') }}
@parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        {{-- SSL: explicación visual. Los registros y filtros siguen siendo del módulo License. --}}
        <p class="text-muted">
            <i class="fa-solid fa-file-signature fa-fw" aria-hidden="true"></i>
            {{ trans('admin/licenses/general.inventory_description') }}
        </p>
        <p class="hidden-print">
            <a href="{{ route('licenses.ssl.alerts') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                {{ trans('admin/licenses/general.ssl_dashboard_title') }}
            </a>
        </p>
        <x-box name="licenses" sr_only_title>

            <x-slot:bulkactions>
                <x-table.bulk-licenses />
            </x-slot:bulkactions>

            <x-table.licenses
                fixed_right_number="2"
                fixed_number="1"
                show_footer="true"
                show_advanced_search="true"
                name="licenses"
                :route="route('api.licenses.index', ['status' => e(request('status'))])"/>

        </x-box>
        <x-shiftclick/>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table')

@stop
