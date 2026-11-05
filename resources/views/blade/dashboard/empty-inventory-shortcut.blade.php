<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('general.dashboard_info') }}</h2>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="progress">
                            <div class="progress-bar progress-bar-yellow" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                <span class="sr-only">{{ trans('general.60_percent_warning') }}</span>
                            </div>
                        </div>
                        <p><strong>{{ trans('general.dashboard_empty') }}</strong></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        @can('create', \App\Models\Asset::class)
                            <a class="btn bg-teal" style="width: 100%" href="{{ route('hardware.create') }}"><i class="fa-solid fa-shield-halved fa-fw" aria-hidden="true"></i> {{ trans('general.new_asset') }}</a>
                        @endcan
                    </div>
                    <div class="col-md-2">
                        @can('create', \App\Models\License::class)
                            <a class="btn bg-maroon" style="width: 100%" href="{{ route('licenses.create') }}"><i class="fa-solid fa-file-signature fa-fw" aria-hidden="true"></i> {{ trans('general.new_license') }}</a>
                        @endcan
                    </div>
                    <div class="col-md-2">
                        @can('create', \App\Models\Accessory::class)
                            <a class="btn bg-orange" style="width: 100%" href="{{ route('accessories.create') }}"><i class="fa-solid fa-shirt fa-fw" aria-hidden="true"></i> {{ trans('general.new_accessory') }}</a>
                        @endcan
                    </div>
                    <div class="col-md-2">
                        @can('create', \App\Models\Consumable::class)
                            <a class="btn bg-purple" style="width: 100%" href="{{ route('consumables.create') }}"><i class="fa-solid fa-helmet-safety fa-fw" aria-hidden="true"></i> {{ trans('general.new_consumable') }}</a>
                        @endcan
                    </div>
                    <div class="col-md-2">
                        @can('create', \App\Models\Component::class)
                            <a class="btn bg-yellow" style="width: 100%" href="{{ route('components.create') }}"><x-icon type="components" class="fa-fw" /> {{ trans('general.new_component') }}</a>
                        @endcan
                    </div>
                    <div class="col-md-2">
                        @can('create', \App\Models\User::class)
                            <a class="btn bg-light-blue" style="width: 100%" href="{{ route('users.create') }}"><x-icon type="users" class="fa-fw" /> {{ trans('general.new_user') }}</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
