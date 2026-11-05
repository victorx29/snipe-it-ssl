@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/accessories/general.checkout') }}
@parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-primary pull-right">{{ trans('general.back') }}</a>
@stop

{{-- Page content --}}
@section('content')

<x-container columns="2">
    <x-page-column class="col-md-7">

        <x-form route="{{ url()->current() }}" id="checkout_form">

            <x-box header="{{ $accessory->name }}">
                <p class="text-muted"><i class="fa-solid fa-shirt fa-fw" aria-hidden="true"></i> {{ trans('admin/accessories/general.checkout_description') }}</p>

            @if ($accessory->name)
                <x-form.static :label="trans('admin/accessories/general.accessory_name')">{{ $accessory->name }}</x-form.static>
            @endif

            @if ($accessory->company)
                <x-form.static :label="trans('general.company')">{!! $accessory->company->present()->formattedNameLink !!}</x-form.static>
            @endif

            @if ($accessory->category)
                <x-form.static :label="trans('general.category')">{!! $accessory->category->present()->formattedNameLink !!}</x-form.static>
            @endif

            <x-form.static :label="trans('admin/accessories/general.total')">{{ $accessory->qty }}</x-form.static>

            <x-form.static :label="trans('admin/accessories/general.remaining')">{{ $accessory->numRemaining() }}</x-form.static>

            {{-- SSL: Uniforms are delivered only to workers in this form. Retain
                 the original field names and endpoint so AccessoryCheckoutController
                 handles the assignment, stock adjustment, and event dispatch. --}}
            <input type="hidden" name="checkout_to_type" value="user" />
            <x-form.static :label="trans('admin/accessories/general.checkout_to')">
                <x-icon type="user" class="fa-fw" />
                {{ trans('admin/accessories/general.worker') }}
            </x-form.static>
            <x-input.user-select
                :label="trans('admin/accessories/general.worker')"
                name="assigned_user"
                :selected="old('assigned_user')"
                :companyId="$accessory->company_id"
            />

            <!-- Checkout quantity -->
            <div class="form-group {{ $errors->has('checkout_qty') ? 'error' : '' }}">
                <label for="checkout_qty" class="col-md-3 control-label">{{ trans('admin/accessories/general.checkout_quantity') }}</label>
                <div class="col-md-7 col-sm-12 required">
                    <div class="col-md-2" style="padding-left: 0">
                        <input class="form-control" type="number" name="checkout_qty" id="checkout_qty" value="{{ old('checkout_qty', 1) }}" min="1" max="{{ $accessory->numRemaining() }}" aria-label="{{ trans('admin/accessories/general.checkout_quantity') }}" />
                    </div>
                </div>
                <div class="col-md-8 col-md-offset-3"><x-form.error name="checkout_qty" /></div>
            </div>

            <x-checkout.checkout-notification-callout :item="$accessory" :category="$accessory->category" />

            <x-form.row
                :label="trans('admin/accessories/general.checkout_notes')"
                :item="$accessory"
                name="note"
                type="textarea"
            />

            <x-slot:customfooter>
                <x-redirect_submit_options
                    index_route="accessories.index"
                    :button_label="trans('admin/accessories/general.checkout')"
                    :options="[
                        'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.accessories')]),
                        'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.accessory')]),
                        'target' => trans('admin/hardware/form.redirect_to_checked_out_to'),
                    ]"
                />
            </x-slot:customfooter>

            </x-box>

        </x-form>

    </x-page-column>

    <x-page-column class="col-md-5">
        <livewire:checkout-target-panel type="accessories" />
    </x-page-column>

</x-container>

@stop
