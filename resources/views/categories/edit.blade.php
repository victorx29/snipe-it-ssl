@extends('layouts/default')

{{-- Page title --}}
@section('title')
    @if ($item->id)
        {{ trans('admin/categories/general.update') }}
    @else
        {{ trans('admin/categories/general.create') }}
    @endif
    @parent
@stop

{{-- Page content --}}
@section('content')

    <x-container class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0">

        <x-form :$item route="{{ ($item->id) ? route('categories.update', ['category' => $item->id]) : route('categories.store') }}">

            <x-box top_submit>
                @if ($item->id)
                    <x-slot:header>{{ $item->name }}</x-slot:header>
                @endif

                <x-form.row
                    :label="trans('general.name')"
                    :$item
                    name="name"
                />

                <x-form.row
                    :label="trans('general.type')"
                    name="category_type"
                    input_div_class="col-md-7 required"
                    :help_text="trans('admin/categories/message.update.cannot_change_category_type')"
                >
                    <x-slot:input>
                        <x-input.select
                            name="category_type"
                            :options="$category_types"
                            :selected="old('category_type', $item->category_type)"
                            :disabled="$item->category_type != '' || $item->itemCount() > 0"
                            style="min-width:350px"
                            aria-label="category_type"
                        />
                    </x-slot:input>
                </x-form.row>

                {{-- SSL: guidance only. Category types and form submission remain native to Snipe-IT. --}}
                @if (! $item->id || in_array($item->category_type, ['accessory', 'consumable'], true))
                    <x-callout type="info" icon="tip" live="polite">
                        <strong>Clasificación de Seguridad y Salud Laboral (SSL)</strong>
                        <p>Uniformes: crea categorías por tipo de prenda (por ejemplo, Camisa corporativa) y selecciona el tipo <strong>Accesorio</strong>. Cada talla debe registrarse como un artículo distinto para mantener existencias y asignaciones independientes.</p>
                        <p>Referencia sugerida: <code>CAM-CORP-MAS-M</code>, <code>CAM-CORP-MAS-L</code>. Por ahora la talla forma parte de la referencia; no es una columna independiente de la base de datos.</p>
                        <p>EPP: utiliza categorías de tipo <strong>Consumible</strong>. Capacitaciones: reserva las categorías <code>CAPACITACION_SSL</code>, <code>CAPACITACION_GH</code> y <code>CAPACITACION_TI</code>, todas de tipo <strong>Accesorio</strong>. Estas no son categorías de Uniformes.</p>
                    </x-callout>
                @endif

                <livewire:category-edit-form
                    :alert-on-response="(bool) old('alert_on_response', $item->alert_on_response)"
                    :default-eula-text="$snipeSettings->default_eula_text"
                    :eula-text="old('eula_text', $item->eula_text)"
                    :require-acceptance="(bool) old('require_acceptance', $item->require_acceptance)"
                    :send-check-in-email="(bool) old('checkin_email', $item->checkin_email)"
                    :use-default-eula="(bool) old('use_default_eula', $item->use_default_eula)"
                />

                <x-input.image-upload :item="$item" :imagePath="app('categories_upload_path')" />

                <x-form.row
                    :label="trans('general.notes')"
                    :$item
                    name="notes"
                    type="textarea"
                    :rows="5"
                    :placeholder="trans('general.placeholders.notes')"
                />

                <fieldset name="color-preferences">
                    <x-form.legend help_text="{{ trans('general.tag_color_help') }}">
                        {{ trans('general.tag_color') }}
                    </x-form.legend>
                    <x-form.row
                        :label="trans('general.tag_color')"
                        :$item
                        name="tag_color"
                        type="colorpicker"
                    />
                </fieldset>

            </x-box>

        </x-form>

    </x-container>

    @if ($snipeSettings->default_eula_text != '')
        {{-- EULA preview modal --}}
        <div class="modal fade" id="eulaModal" tabindex="-1" role="dialog" aria-labelledby="eulaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h2 class="modal-title" id="eulaModalLabel">{{ trans('admin/settings/general.default_eula_text') }}</h2>
                    </div>
                    <div class="modal-body">
                        {{ \App\Models\Setting::getDefaultEula() }}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop
