<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Providers;

use Illuminate\Support\ServiceProvider;

class AlimentacaoEscolarServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-alimentacao-escolar-package';

    public function register(): void
    {
        // Services são value objects — instanciar com `new` nos callers (não bind no container).
    }

    public function boot(): void
    {
    }
}
