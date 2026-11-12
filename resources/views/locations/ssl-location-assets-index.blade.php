@extends('layouts/default')

@section('title')
    Constancias SSL por sede - Activos
    @parent
@stop

@section('content')
    <x-container>
        <x-page-column class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">Constancias de activos entregados por sede</h2>
                </div>
                <div class="box-body">
                    <p>Elige una ubicación física para generar su nota de entrega institucional.
                        Solo se relacionan los activos <strong>directamente asignados a la ubicación</strong>,
                        no los activos asignados a trabajadores que están físicamente en esa sede.</p>
                    <form method="GET" action="{{ route('ssl.locations.receipts.index') }}" class="form-inline" style="margin-bottom: 16px;">
                        <label class="sr-only" for="ssl-location-search">Buscar sede</label>
                        <input id="ssl-location-search" class="form-control" type="search" name="q"
                               value="{{ request('q') }}" maxlength="100" placeholder="Buscar sede por nombre">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                        <a class="btn btn-default" href="{{ route('ssl.locations.receipts.index') }}">Limpiar</a>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Sede física</th><th>Empresa</th><th>Responsable de sede</th><th>Constancia</th></tr></thead>
                            <tbody>
                            @forelse ($locations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td>{{ $location->company?->name ?: 'No registrada' }}</td>
                                    <td>{{ $location->manager?->display_name ?: 'No registrado' }}</td>
                                    <td>
                                        <a class="btn btn-primary btn-sm"
                                           href="{{ route('ssl.locations.receipts.show', $location->id) }}"
                                           target="_blank" rel="noopener"
                                           aria-label="Preparar constancia de {{ $location->name }}">
                                            <i class="fa-solid fa-print fa-fw" aria-hidden="true"></i>
                                            Preparar constancia
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No se encontraron sedes disponibles con tus permisos actuales.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-page-column>
    </x-container>
@stop
