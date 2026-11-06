@extends('layouts/default')

@section('title')
    {{ trans('admin/licenses/general.bulk_checkin_title') }}
    @parent
@stop

@section('content')
    <x-bulk-checkin-form
        :action_route="route('licenses.bulkcheckin.store')"
        :ids="$ids"
        :count="count($seats)"
        :return_url="$return_url"
    >
        <table class="table table-striped" style="margin-bottom: 25px; border-bottom: 1px solid light-dark(#eeeeee, #656464);">
            <thead>
                <tr>
                    <th>{{ trans('general.id') }}</th>
                    <th>{{ trans('admin/licenses/general.permit_name') }}</th>
                    <th>{{ trans('general.checked_out_to') }}</th>
                    <th>{{ trans('admin/hardware/table.checkout_date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($seats as $seat)
                    <tr>
                        <td>{{ $seat->id }}</td>
                        <td>{{ $seat->license?->name }}</td>
                        <td>{{ $seat->user?->present()?->fullName ?? $seat->asset?->present()?->fullName ?? trans('general.deleted') }}</td>
                        <td>{{ \App\Helpers\Helper::getFormattedDateObject($seat->created_at, 'datetime', false) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-bulk-checkin-form>
@stop
