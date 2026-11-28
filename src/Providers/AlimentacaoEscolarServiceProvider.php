<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Providers;

use App\Support\PublicModuleRegistry;
use iEducar\Packages\BuritiAlimentacaoEscolar\Console\AlimentacaoDemoCommand;
use iEducar\Packages\BuritiAlimentacaoEscolar\Console\AlimentacaoSeedTacoCommand;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\Identidade;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\MenuFluxo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AlimentacaoEscolarServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-alimentacao-escolar-package';

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/alimentacao.php', 'alimentacao');
        // Fora de runningInConsole(): o check do Ambiente Laguna (HTTP) usa Artisan::all().
        $this->commands([AlimentacaoDemoCommand::class, AlimentacaoSeedTacoCommand::class]);
    }

    public function boot(): void
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::registrar();
        }
        $root = dirname(__DIR__, 2);
        $this->loadViewsFrom($root.'/resources/views', 'alimentacao');

        PublicModuleRegistry::register('alimentacao-publico', [
            'package' => self::PACKAGE,
            'titulo' => (string) config('alimentacao.public.titulo', 'Alimentação escolar'),
            'descricao' => 'Acesso público à merenda (quando habilitado)',
            'url' => static function (): ?string {
                $url = config('alimentacao.public.url');

                return filled($url) ? (string) $url : null;
            },
            'icone' => 'fa-cutlery',
            'css_class' => 'public-module',
            'aparecer_na_home' => (bool) config('alimentacao.public.aparecer_na_home', false),
            'ordem' => (int) config('alimentacao.public.ordem', 30),
        ]);

        if ($this->app->runningInConsole()) {
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

        View::composer('alimentacao::*', function ($view): void {
            $view->with('identidade', Identidade::tokens());
            $view->with('menuGrupos', MenuFluxo::grupos([
                'painel' => true,
                'cardapio' => true,
                'cardapio_lote' => true,
                'itens' => true,
                'perfil' => true,
                'restricoes' => true,
                'mapa' => true,
                'balanco' => true,
                'sobras' => true,
                'fichas_tecnicas' => true,
                'cronograma' => true,
                'historico' => true,
                'config' => true,
                'demo' => true,
            ]));
        });
    }
}
