@extends('layouts/default')
@section('title')
    {{ $training->exists ? 'Editar sesión de capacitación' : 'Nueva sesión de capacitación' }}
    @parent
@stop
@section('content')
<x-container class="col-md-10 col-md-offset-1">
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $training->exists ? 'Editar sesión' : 'Registrar nueva capacitación' }}</h3>
        </div>
        <form method="POST" action="{{ $training->exists ? route('ssl.trainings.update', $training->id) : route('ssl.trainings.store') }}" class="form-horizontal">
            @csrf
            @if ($training->exists) @method('PUT') @endif
            <div class="box-body">
                <div class="alert alert-info">
                    Las capacitaciones no son entregas de Uniformes. Esta pantalla registra datos de la sesión; <strong>no</strong> registra asistentes ni calcula horas-hombre todavía.
                </div>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Revisa los campos:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="form-group">
                    <label for="training-name" class="col-sm-3 control-label">Nombre de capacitación *</label>
                    <div class="col-sm-8"><input class="form-control" id="training-name" name="name" maxlength="255" required value="{{ old('name', $training->name) }}"></div>
                </div>
                <div class="form-group">
                    <label for="training-category" class="col-sm-3 control-label">Área responsable *</label>
                    <div class="col-sm-8">
                        <select id="training-category" class="form-control" name="category_id" required>
                            <option value="">Seleccione un área</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $training->category_id) === (string) $category->id)>{{ \App\Models\User::sslTrainingAreaLabel($category->name) }}</option>
                            @endforeach
                        </select>
                        <p class="help-block">Categorías técnicas: CAPACITACION_SSL, CAPACITACION_GH o CAPACITACION_TI (tipo Accesorio).</p>
                        @if ($categories->isEmpty())<p class="text-danger">Primero debes crear una categoría de capacitación en Configuraciones → Categorías.</p>@endif
                    </div>
                </div>
                <x-input.company-select
                    :label="trans('general.company')"
                    name="company_id"
                    :selected="old('company_id', $training->company_id)"
                />
                <x-input.location-select
                    :label="trans('general.location')"
                    name="location_id"
                    :selected="old('location_id', $training->location_id)"
                />
                <div class="form-group">
                    <label for="training-code" class="col-sm-3 control-label">Código / referencia</label>
                    <div class="col-sm-5"><input class="form-control" id="training-code" name="model_number" maxlength="191" value="{{ old('model_number', $training->model_number) }}" placeholder="Ej. SSL-INDUCCION-001"></div>
                </div>
                <div class="form-group">
                    <label for="training-start" class="col-sm-3 control-label">Inicio de la sesión *</label>
                    <div class="col-sm-5"><input type="datetime-local" class="form-control" id="training-start" name="ssl_training_starts_at" required value="{{ old('ssl_training_starts_at', $training->ssl_training_starts_at?->format('Y-m-d\\TH:i')) }}"></div>
                </div>
                <div class="form-group">
                    <label for="training-end" class="col-sm-3 control-label">Finalización de la sesión *</label>
                    <div class="col-sm-5"><input type="datetime-local" class="form-control" id="training-end" name="ssl_training_ends_at" required value="{{ old('ssl_training_ends_at', $training->ssl_training_ends_at?->format('Y-m-d\\TH:i')) }}"></div>
                </div>
                <div class="form-group">
                    <label for="training-minutes" class="col-sm-3 control-label">Duración efectiva (minutos) *</label>
                    <div class="col-sm-3"><input type="number" min="1" max="1000000" step="1" class="form-control" id="training-minutes" name="ssl_training_duration_minutes" required value="{{ old('ssl_training_duration_minutes', $training->ssl_training_duration_minutes) }}" placeholder="Ej. 90"></div>
                    <div class="col-sm-5 help-block">Registra minutos reales de formación, excluyendo descansos. 90 minutos = 1,5 horas.</div>
                </div>
                <div class="form-group">
                    <label for="training-mode" class="col-sm-3 control-label">Modalidad *</label>
                    <div class="col-sm-5"><select class="form-control" id="training-mode" name="ssl_training_modality" required>
                        @foreach (['presencial' => 'Presencial', 'virtual' => 'Virtual', 'mixta' => 'Mixta'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('ssl_training_modality', $training->ssl_training_modality ?? 'presencial') === $value)>{{ $label }}</option>
                        @endforeach
                    </select></div>
                </div>
                <div class="form-group">
                    <label for="training-facilitator" class="col-sm-3 control-label">Facilitador / instructor</label>
                    <div class="col-sm-8"><input class="form-control" id="training-facilitator" name="ssl_training_facilitator" maxlength="191" value="{{ old('ssl_training_facilitator', $training->ssl_training_facilitator) }}"></div>
                </div>
                <div class="form-group">
                    <label for="training-status" class="col-sm-3 control-label">Estado de la sesión *</label>
                    <div class="col-sm-5"><select class="form-control" id="training-status" name="ssl_training_status" required>
                        <option value="planned" @selected(old('ssl_training_status', $training->ssl_training_status ?? 'planned') === 'planned')>Programada</option>
                        <option value="completed" @selected(old('ssl_training_status', $training->ssl_training_status) === 'completed')>Realizada (asistencia pendiente de registrar)</option>
                        <option value="cancelled" @selected(old('ssl_training_status', $training->ssl_training_status) === 'cancelled')>Cancelada</option>
                    </select></div>
                </div>
                <div class="form-group">
                    <label for="training-notes" class="col-sm-3 control-label">Descripción / observaciones</label>
                    <div class="col-sm-8"><textarea class="form-control" id="training-notes" name="notes" rows="4">{{ old('notes', $training->notes) }}</textarea></div>
                </div>
            </div>
            <div class="box-footer">
                <a href="{{ $training->exists ? route('ssl.trainings.show', $training->id) : route('ssl.trainings.index') }}" class="btn btn-default">Cancelar</a>
                <button type="submit" class="btn btn-primary pull-right" @disabled($categories->isEmpty())>
                    <i class="fa fa-save" aria-hidden="true"></i> Guardar sesión
                </button>
            </div>
        </form>
    </div>
</x-container>
@stop
