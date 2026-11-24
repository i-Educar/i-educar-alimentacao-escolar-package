<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Providers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
use Illuminate\Support\ServiceProvider;

class AlimentacaoEscolarServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-alimentacao-escolar-package';

    public function register(): void
    {
        $this->app->singleton(CardapioDia::class);
        $this->app->singleton(RestricaoAlimentar::class);
        $this->app->singleton(MapaDistribuicao::class);
    }

    public function boot(): void
    {
    }
}
