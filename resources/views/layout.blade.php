<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Alimentação escolar')</title>
    <link rel="stylesheet" href="{{ url('/intranet/styles/font-awesome.css') }}">
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#0f6b4c' }};
            --destaque: {{ $identidade['destaque'] ?? '#2d8a5e' }};
            --clara: {{ $identidade['clara'] ?? '#e5f6ec' }};
            --nav: #15222b;
            --nav-texto: #d5e0e6;
            --fundo: #e7ebef;
            --tinta: #1a242b;
            --suave: #5b6b76;
            --linha: #d3dbe2;
            --ok: #17663f;
            --ok-fundo: #e5f6ec;
            --erro: #9b2c2c;
            --erro-fundo: #fdeceb;
            --cor-tela: var(--cor);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; font-size: 14px; background: var(--fundo); color: var(--tinta); line-height: 1.45; }
        .app { display: flex; min-height: 100vh; }
        .lateral { width: 248px; flex: none; background: var(--nav); color: var(--nav-texto); position: sticky; top: 0; height: 100vh; overflow: auto; }
        .marca { display: flex; gap: 10px; align-items: center; color: #fff; text-decoration: none; padding: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .marca strong { display: block; font-size: .95rem; }
        .marca small { display: block; font-weight: 400; color: #8aa0ad; }
        .icone { width: 36px; height: 36px; background: var(--destaque); color: #fff; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .modulo { padding: 8px 10px 2px; }
        .modulo-nome { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 8px; margin: 0; padding: 8px; background: transparent; border: 0; color: #8aa0ad; font: inherit; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; cursor: pointer; text-align: left; }
        .modulo-itens { display: block; }
        .modulo a { display: flex; align-items: center; gap: 10px; color: var(--nav-texto); text-decoration: none; border-radius: 4px; padding: 7px 10px; }
        .modulo a i { width: 16px; text-align: center; }
        .modulo a:hover { background: rgba(255,255,255,.06); }
        .modulo a.ativo { background: var(--cor); color: #fff; font-weight: 700; }
        .miolo { flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .topo { position: sticky; top: 0; z-index: 20; min-height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 18px; background: #fff; border-bottom: 1px solid var(--linha); }
        .migalha { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0; color: var(--suave); font-size: 13px; }
        .migalha a { color: var(--cor); font-weight: 700; text-decoration: none; }
        .migalha strong { color: var(--tinta); }
        main { padding: 18px 20px 28px; flex: 1; }
        .cabeca-tela { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: -18px -20px 16px; padding: 12px 12px 12px 20px; background: #fff; border-bottom: 1px solid var(--linha); border-left: 4px solid var(--cor-tela); }
        .identidade-tela { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .marca-tela { width: 40px; height: 40px; border-radius: 4px; display: grid; place-items: center; flex: none; background: var(--cor-tela); color: #fff; font-size: 16px; }
        .cabeca-tela h1 { margin: 0; font-size: 1.25rem; line-height: 1.2; }
        .cabeca-tela .lead { margin: 2px 0 0; color: var(--suave); }
        .acoes-tela { margin-left: auto; display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px; }
        .botao { display: inline-flex; align-items: center; gap: 8px; background: var(--cor-tela); color: #fff; border: 0; border-radius: 4px; padding: 8px 12px; font: inherit; text-decoration: none; cursor: pointer; }
        .botao.claro { background: #fff; color: var(--cor-tela); border: 1px solid color-mix(in srgb, var(--cor-tela) 35%, var(--linha)); }
        .conteudo a.botao { color: #fff; }
        .conteudo a.botao.claro { color: var(--cor-tela); }
        .flash { border-radius: 8px; padding: 12px 14px; margin: 0 0 16px; }
        .flash.ok { background: var(--ok-fundo); color: var(--ok); }
        .flash.erro { background: var(--erro-fundo); color: var(--erro); }
        .flash.alerta { background: #fff6e8; color: #b86e00; }
        .bloco { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 12px 14px; margin: 0 0 12px; border-top: 3px solid var(--cor-tela); }
        .bloco h2 { margin: 0 0 10px; font-size: 1.05rem; }
        .numeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin: 0 0 16px; }
        .numeros article { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 12px; border-top: 3px solid var(--cor-tela); }
        .numeros span { display: block; color: var(--suave); font-size: .8rem; }
        .numeros strong { font-size: 1.25rem; }
        .tabela-wrap { overflow-x: auto; background: #fff; border: 1px solid var(--linha); border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--linha); }
        th { background: var(--clara); color: var(--cor); font-size: .86rem; }
        form.filtro { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin: 0 0 14px; }
        form.filtro label { display: grid; gap: 4px; font-size: .8rem; font-weight: 700; color: var(--suave); }
        form.filtro input, form.filtro select { min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 4px; font: inherit; }
        .atalhos { list-style: none; margin: 10px 0 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px; }
        .atalhos a { display: flex; align-items: center; gap: 8px; min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 4px; color: var(--tinta); text-decoration: none; background: #f7f9fa; font-weight: 600; }
        .atalhos a i { color: var(--destaque); width: 16px; text-align: center; }
        .atalhos a:hover { border-color: var(--destaque); background: #fff; }
        footer.rodape { background: var(--nav); color: var(--nav-texto); border-top: 3px solid var(--destaque); padding: 12px 18px; font-size: .82rem; }
        @media (max-width: 860px) {
            .lateral { display: none; }
        }
    </style>
</head>
<body>
@php
    $path = request()->path();
    $grupos = $menuGrupos ?? [];
@endphp
<div class="app">
    <aside class="lateral">
        <a class="marca" href="{{ url('/alimentacao-escolar') }}">
            <span class="icone"><i class="fa {{ $identidade['icone'] ?? 'fa-cutlery' }}"></i></span>
            <span>
                <strong>{{ $identidade['nome'] ?? 'Alimentação escolar' }}</strong>
                <small>Laguna · PNAE</small>
            </span>
        </a>
        @foreach($grupos as $grupo)
            <nav class="modulo aberto">
                <button type="button" class="modulo-nome">{{ $grupo['nome'] }} <i class="fa fa-chevron-down"></i></button>
                <div class="modulo-itens">
                    @foreach($grupo['itens'] as $item)
                        <a href="{{ url($item['url']) }}" class="@if(str_starts_with('/'.$path, $item['url']) || ($item['url'] === '/alimentacao-escolar' && $path === 'alimentacao-escolar')) ativo @endif">
                            <i class="fa {{ $item['icone'] }}"></i> {{ $item['rotulo'] }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endforeach
    </aside>
    <div class="miolo">
        <header class="topo">
            <p class="migalha">
                <a href="{{ url('/') }}">i-Educar</a> /
                <a href="{{ url('/alimentacao-escolar') }}">Alimentação</a> /
                <strong>@yield('migalha', 'Painel')</strong>
            </p>
            <div class="acoes-tela">
                @yield('acoes-topo')
            </div>
        </header>
        <main class="conteudo">
            @if(session('ok'))
                <p class="flash ok">{{ session('ok') }}</p>
            @endif
            @if(session('erro'))
                <p class="flash erro">{{ session('erro') }}</p>
            @endif
            @yield('alimentacao')
        </main>
        <footer class="rodape">Alimentação escolar · Buriti / Laguna PE-77 · cor-base {{ $identidade['cor'] ?? '' }}</footer>
    </div>
</div>
</body>
</html>
