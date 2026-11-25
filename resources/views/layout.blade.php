<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Alimentação escolar')</title>
    <link rel="stylesheet" href="/intranet/styles/font-awesome.css">
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#0f6b4c' }};
            --destaque: {{ $identidade['destaque'] ?? '#2d8a5e' }};
            --clara: {{ $identidade['clara'] ?? '#e5f6ec' }};
            --nav: #15222b; --nav-texto: #d5e0e6; --fundo: #e8f0ea;
            --tinta: #1a242b; --suave: #5b6b76; --linha: #c9dbd0;
            --ok: #17663f; --ok-fundo: #e5f6ec; --erro: #9b2c2c; --erro-fundo: #fdeceb;
            --ambar: #b86e00; --ambar-fundo: #fff6e8; --cor-tela: var(--cor);
            --cafe: #c17a2f; --almoco: #0f6b4c; --lanche: #1a7a6d; --jantar: #2a5f6e;
            --espaco: 16px; --espaco-lg: 22px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; font-size: 14px; color: var(--tinta);
            background: radial-gradient(900px 360px at 8% -8%, color-mix(in srgb, var(--clara) 80%, transparent), transparent 55%), linear-gradient(180deg, #f3f7f4, var(--fundo)); }
        .app { display: flex; min-height: 100vh; }
        .lateral { width: 248px; flex: none; background: linear-gradient(180deg, color-mix(in srgb, var(--cor) 35%, #15222b), var(--nav)); color: var(--nav-texto); }
        .marca { display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; padding: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .marca .marca-texto { display: none; }
        .marca .marca-titulo { display: block; font-size: .82rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; line-height: 1.15; }
        .marca .marca-desc { display: block; color: #8aa0ad; font-weight: 400; font-size: .72rem; line-height: 1.25; }
                .icone { width: 40px; height: 40px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, var(--destaque), var(--cor)); color: #fff; box-shadow: 0 8px 18px color-mix(in srgb, var(--cor) 35%, transparent); }
        .modulo { padding: 8px 10px; }
        .modulo-nome { width: 100%; display: flex; justify-content: space-between; margin: 0; padding: 8px; background: 0; border: 0; color: #8aa0ad; font: inherit; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; }
        .modulo a { display: flex; align-items: center; gap: 10px; color: var(--nav-texto); text-decoration: none; border-radius: 10px; padding: 8px 10px; }
        .modulo a i { width: 18px; text-align: center; }
        .modulo a:hover, .modulo a.ativo { background: color-mix(in srgb, var(--cor) 88%, #fff); color: #fff; font-weight: 700; }
        .miolo { flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
        main { padding: var(--espaco-lg) 22px 28px; flex: 1; display: flex; flex-direction: column; gap: var(--espaco); }
        .cabeca-tela { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: calc(-1 * var(--espaco-lg)) -22px var(--espaco); padding: 16px 22px;
            background: linear-gradient(120deg, color-mix(in srgb, var(--clara) 70%, #fff), #fff 55%); border-bottom: 1px solid var(--linha); border-left: 4px solid var(--cor-tela); }
        .identidade-tela { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .marca-tela { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; flex: none; background: linear-gradient(145deg, var(--destaque), var(--cor-tela)); color: #fff; font-size: 18px; box-shadow: 0 10px 20px color-mix(in srgb, var(--cor-tela) 28%, transparent); }
        .cabeca-tela h1 { margin: 0; font-size: 1.25rem; } .cabeca-tela .lead { margin: 4px 0 0; color: var(--suave); line-height: 1.4; }
        .acoes-tela { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .botao { display: inline-flex; align-items: center; gap: 8px; background: var(--cor-tela); color: #fff; border: 0; border-radius: 10px; padding: 8px 12px; font: inherit; text-decoration: none; cursor: pointer; }
        .botao.claro { background: #fff; color: var(--cor-tela); border: 1px solid color-mix(in srgb, var(--cor-tela) 35%, var(--linha)); }
        .flash { border-radius: 12px; padding: 12px 14px; margin: 0; } .flash.ok { background: var(--ok-fundo); color: var(--ok); } .flash.erro { background: var(--erro-fundo); color: var(--erro); } .flash.alerta { background: var(--ambar-fundo); color: var(--ambar); }
        .bloco { background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 16px 18px; margin: 0; border-top: 3px solid var(--cor-tela); box-shadow: 0 10px 24px rgba(21,34,43,.04); }
        .bloco h2 { margin: 0 0 12px; display: flex; align-items: center; gap: 8px; font-size: 1.05rem; }
        .bloco + .bloco, .bloco + .bandeja-grid, .bloco + .mapa-rede, .bandeja-grid + .bloco, .numeros + .bloco, .filtro + .bloco { margin-top: 0; }
        .numeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin: 0; }
        .numeros article { --tom: var(--cor-tela); background: #fff; border: 1px solid color-mix(in srgb, var(--tom) 30%, var(--linha)); border-top: 3px solid var(--tom); border-radius: 14px; padding: 14px; box-shadow: 0 8px 18px rgba(21,34,43,.05); animation: sobe .4s ease both; }
        .numeros .kpi-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; margin: 0 0 8px; background: color-mix(in srgb, var(--tom) 12%, #fff); color: var(--tom); border: 1px solid color-mix(in srgb, var(--tom) 22%, #fff); }
        .numeros span { display: block; color: var(--suave); font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .numeros strong { font-size: 1.4rem; letter-spacing: -.03em; }
        .atalhos { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px; }
        .atalhos a { display: grid; grid-template-columns: 42px 1fr; gap: 10px; align-items: center; min-height: 72px; padding: 12px 14px; border: 1px solid var(--linha); border-radius: 14px; text-decoration: none; color: var(--tinta); background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 55%, #fff), #fff); font-weight: 700; transition: transform .15s ease, border-color .15s ease; }
        .atalhos a i { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: var(--destaque); }
        .atalhos a small { display: block; font-weight: 500; color: var(--suave); margin-top: 2px; }
        .atalhos a:hover { transform: translateY(-2px); border-color: var(--destaque); }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--linha); } th { background: var(--clara); color: var(--cor); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end; }
        .form-grid label { display: grid; gap: 4px; font-size: .8rem; font-weight: 700; color: var(--suave); }
        .form-grid input, .form-grid select, .form-grid textarea { min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; background: #fff; }
        .form-grid textarea { min-height: 70px; resize: vertical; }
        .form-grid .col-wide { grid-column: 1 / -1; }
        .form-grid .acoes { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; }
        .filtro { display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin: 0; padding: 14px 16px; background: #fff; border: 1px solid var(--linha); border-radius: 14px; }
        .filtro label { display: grid; gap: 4px; font-size: .8rem; font-weight: 700; color: var(--suave); }
        .filtro input, .filtro select { min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; }

        /* Montagem visual do cardápio */
        .montagem-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
        .bandeja-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin: 0; }
        .bandeja { --tom-ref: var(--almoco); position: relative; overflow: hidden; background: #fff; border: 1px solid color-mix(in srgb, var(--tom-ref) 38%, var(--linha)); border-top: 4px solid var(--tom-ref); border-radius: 14px; padding: 14px; box-shadow: 0 10px 24px rgba(21,34,43,.05); animation: sobe .4s ease both; }
        .bandeja.cafe { --tom-ref: var(--cafe); } .bandeja.almoco { --tom-ref: var(--almoco); } .bandeja.lanche { --tom-ref: var(--lanche); }
        .bandeja-topo { display: flex; align-items: center; gap: 12px; margin: 0 0 12px; }
        .bandeja-ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, color-mix(in srgb, var(--tom-ref) 75%, #fff), var(--tom-ref)); }
        .bandeja h2, .bandeja h3 { margin: 0; font-size: 1.02rem; color: var(--tom-ref); } .bandeja .meta { margin: 2px 0 0; color: var(--suave); font-size: .8rem; }
        .prato { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 10px; padding: 12px; min-height: 72px; border-radius: 12px; background: radial-gradient(circle at 50% 45%, #fff 0 34%, color-mix(in srgb, var(--tom-ref) 10%, #f4f7f5) 35% 100%); border: 1px dashed color-mix(in srgb, var(--tom-ref) 30%, var(--linha)); }
        .chip-item { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #fff; border: 1px solid color-mix(in srgb, var(--tom-ref) 22%, var(--linha)); font-weight: 600; font-size: .84rem; }
        .catalogo-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 140px; overflow: auto; padding: 4px 0; }
        .chip-pick { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--linha); background: #f7fbf8; color: var(--tinta); font: inherit; font-size: .8rem; font-weight: 600; cursor: pointer; }
        .chip-pick:hover { border-color: var(--tom-ref, var(--cor)); }
        .chip-pick.on { background: color-mix(in srgb, var(--tom-ref, var(--cor)) 16%, #fff); border-color: var(--tom-ref, var(--cor)); color: var(--tom-ref, var(--cor)); }
        .chip-pick .af { font-size: .68rem; opacity: .75; }
        .chip-pick .x { opacity: .55; margin-left: 2px; }
        .livre-itens { width: 100%; margin-top: 8px; min-height: 34px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; }

        .alerta-card { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 12px; margin: 0 0 8px; background: var(--ambar-fundo); border: 1px solid color-mix(in srgb, var(--ambar) 25%, #f0e0c4); color: #7a4a00; }
        .alerta-card:last-child { margin-bottom: 0; }
        .alerta-card.erro { background: var(--erro-fundo); border-color: color-mix(in srgb, var(--erro) 30%, #fff); color: var(--erro); }
        .alerta-card.ok { background: var(--ok-fundo); border-color: color-mix(in srgb, var(--ok) 30%, #fff); color: var(--ok); }
        .alerta-card .selo { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; flex: none; background: #fff; color: var(--ambar); }
        .badge { display: inline-flex; padding: 3px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge.ok { background: var(--ok-fundo); color: var(--ok); } .badge.warn { background: var(--ambar-fundo); color: var(--ambar); } .badge.fail { background: var(--erro-fundo); color: var(--erro); }
        .escolas-lote { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px 10px; max-height: 220px; overflow: auto; padding: 8px; border: 1px solid var(--linha); border-radius: 10px; background: #f7fbf8; }
        .escolas-lote label { display: flex; gap: 8px; align-items: center; font-weight: 600; color: var(--tinta); font-size: .86rem; }

        /* Mapa / dashboard rede */
        .mapa-rede { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; margin: 0; }
        .escola-card { background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 14px; box-shadow: 0 8px 18px rgba(21,34,43,.04); animation: sobe .4s ease both; border-top: 3px solid var(--cor); }
        .escola-card header { display: flex; gap: 12px; align-items: center; margin: 0 0 12px; }
        .escola-card .predio { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, var(--destaque), var(--cor)); flex: none; }
        .escola-card h3 { margin: 0; font-size: .98rem; }
        .escola-card .qtd { margin: 2px 0 0; color: var(--suave); font-size: .8rem; }
        .barra-dist { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .barra-dist .rotulo { display: flex; justify-content: space-between; gap: 8px; font-size: .82rem; margin: 0 0 4px; }
        .trilha { height: 8px; border-radius: 999px; background: #e8f0ea; overflow: hidden; }
        .trilha i { display: block; height: 100%; width: var(--pct, 0%); border-radius: inherit; background: linear-gradient(90deg, var(--destaque), var(--cor)); }
        .cena-vazia { text-align: center; padding: 28px 18px; color: var(--suave); }
        .cena-vazia i { font-size: 2rem; color: var(--destaque); margin: 0 0 10px; display: block; }
        .cena-vazia p { margin: 0; line-height: 1.45; }

        /* Select com buscador */
        .busca-select { position: relative; }
        .busca-select-input { width: 100%; min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; background: #fff; }
        .busca-select-lista { display: none; position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px); max-height: 220px; overflow: auto; margin: 0; padding: 6px; list-style: none; background: #fff; border: 1px solid var(--linha); border-radius: 10px; box-shadow: 0 12px 28px rgba(21,34,43,.12); }
        .busca-select.aberto .busca-select-lista { display: block; }
        .busca-select-lista li { padding: 8px 10px; border-radius: 8px; cursor: pointer; font-size: .86rem; }
        .busca-select-lista li:hover, .busca-select-lista li.ativo { background: var(--clara); color: var(--cor); }
        .busca-select-lista li em { color: var(--suave); font-style: normal; font-size: .78rem; }
        .busca-select-lista .vazio { color: var(--suave); cursor: default; }

        .painel-grade { display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--espaco); align-items: start; }
        @media (max-width: 980px) { .painel-grade { grid-template-columns: 1fr; } }
        @keyframes sobe { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @media (max-width: 860px) { .lateral { display: none; } .cabeca-tela { flex-direction: column; align-items: flex-start; } }
    </style>
    @include('buriti-ui::estilos')
</head>
<body>
@php $path = request()->path(); $grupos = $menuGrupos ?? []; @endphp
<div class="app">
    <aside class="lateral">
        <a class="marca" href="{{ url('/alimentacao-escolar') }}" title="{{ $identidade['nome'] ?? '' }}">
            <span class="icone"><i class="fa {{ $identidade['icone'] ?? 'fa-cutlery' }}"></i></span>
        </a>
        @foreach($grupos as $grupo)
            <nav class="modulo">
                <div class="modulo-nome">{{ $grupo['nome'] }}</div>
                @foreach($grupo['itens'] as $item)
                    <a href="{{ url($item['url']) }}" class="@if(request()->is(ltrim($item['url'], '/'))) ativo @endif">
                        <i class="fa {{ $item['icone'] }}"></i> {{ $item['rotulo'] }}
                    </a>
                @endforeach
            </nav>
        @endforeach
    </aside>
    <div class="miolo">
        @php
            $homeModulo = url('/alimentacao-escolar');
            $nomeModulo = $identidade['nome'] ?? 'Alimentação escolar';
            $descricaoModulo = $identidade['descricao'] ?? null;
            $menuGrupos = $menuGrupos ?? ($grupos ?? []);
        @endphp
        @include('buriti-ui::topo')
        <main>
            @if(session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
            @if(session('erro')) <p class="flash erro">{{ session('erro') }}</p> @endif
            @if(session('alerta')) <p class="flash alerta">{{ session('alerta') }}</p> @endif

            <header class="cabeca-tela">
                <div class="identidade-tela">
                    <span class="marca-tela"><i class="fa @yield('icone-tela', $identidade['icone'] ?? 'fa-cutlery')"></i></span>
                    <div>
                        <h1>@hasSection('titulo-tela')@yield('titulo-tela')@else@yield('migalha', 'Painel')@endif</h1>
                        @hasSection('lead')
                            <p class="lead">@yield('lead')</p>
                        @endif
                    </div>
                </div>
                <div class="acoes-tela">@yield('acoes-topo')</div>
            </header>

            @yield('conteudo')
        </main>
        @include('buriti-ui::rodape', ['moduloRodape' => 'Alimentação escolar'])
    </div>
</div>
<script>
(function () {
    /* Chip picker do cardápio */
    document.querySelectorAll('[data-montagem]').forEach(function (box) {
        var campo = box.getAttribute('data-campo');
        var hidden = box.querySelector('input[data-itens-hidden]');
        var prato = box.querySelector('[data-prato]');
        function sync() {
            var nomes = [];
            box.querySelectorAll('.chip-pick.on').forEach(function (b) { nomes.push(b.getAttribute('data-nome')); });
            var livre = (box.querySelector('.livre-itens') || {}).value || '';
            livre.split(',').forEach(function (p) {
                p = p.trim();
                if (p && nomes.indexOf(p) === -1) nomes.push(p);
            });
            if (hidden) hidden.value = nomes.join(', ');
            if (prato) {
                prato.innerHTML = '';
                if (!nomes.length) {
                    prato.innerHTML = '<span style="color:var(--suave)">Nenhum item ainda — clique no catálogo</span>';
                    return;
                }
                nomes.forEach(function (n) {
                    var s = document.createElement('span');
                    s.className = 'chip-item';
                    s.innerHTML = '<i class="fa fa-check"></i> ' + n;
                    prato.appendChild(s);
                });
            }
        }
        box.querySelectorAll('.chip-pick').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.classList.toggle('on');
                sync();
            });
        });
        var livre = box.querySelector('.livre-itens');
        if (livre) livre.addEventListener('input', sync);
        sync();
    });

    /* Select com buscador */
    document.querySelectorAll('[data-busca-select]').forEach(function (wrap) {
        var input = wrap.querySelector('.busca-select-input');
        var hidden = wrap.querySelector('input[type="hidden"]');
        var lista = wrap.querySelector('.busca-select-lista');
        var itens = Array.prototype.slice.call(lista.querySelectorAll('li[data-id]'));
        function filtrar(q) {
            q = (q || '').toLowerCase().trim();
            var n = 0;
            itens.forEach(function (li) {
                var ok = !q || (li.getAttribute('data-texto') || '').indexOf(q) !== -1;
                li.style.display = ok ? '' : 'none';
                if (ok) n++;
            });
            var vazio = lista.querySelector('.vazio');
            if (vazio) vazio.style.display = n ? 'none' : '';
        }
        input.addEventListener('focus', function () { wrap.classList.add('aberto'); filtrar(input.value); });
        input.addEventListener('input', function () {
            if (hidden) hidden.value = '';
            filtrar(input.value);
            wrap.classList.add('aberto');
        });
        itens.forEach(function (li) {
            li.addEventListener('click', function () {
                if (hidden) hidden.value = li.getAttribute('data-id');
                input.value = li.getAttribute('data-label');
                wrap.classList.remove('aberto');
            });
        });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) wrap.classList.remove('aberto');
        });
    });
})();
</script>
</body>
</html>
