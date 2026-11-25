<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Providers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Console\AlimentacaoDemoCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AlimentacaoEscolarServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-alimentacao-escolar-package';

    public function register(): void
    {
        // Services são value objects — instanciar com `new` nos callers.
    }

    public function boot(): void
    {
        $root = dirname(__DIR__, 2);
        $this->loadViewsFrom($root.'/resources/views', 'alimentacao');

        if ($this->app->runningInConsole()) {
            $this->commands([AlimentacaoDemoCommand::class]);

            if (\App\Support\Tenancy\PackageTenant::allows(self::PACKAGE)) {
                $this->loadMigrationsFrom($root.'/database/migrations');
            }
        }

        Route::middleware([
            'web',
            'ieducar.navigation',
            'ieducar.footer',
            'ieducar.xssbypass',
            'ieducar.suspended',
            'auth',
            'ieducar.checkresetpassword',
        ])->group($root.'/routes/web.php');
    }
}
