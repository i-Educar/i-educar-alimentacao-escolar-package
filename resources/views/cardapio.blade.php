<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cardápio {{ $data }}</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:2rem;background:#f6f7f4;color:#1a1a1a}
        .card{background:#fff;padding:1.25rem 1.5rem;border-radius:8px;max-width:48rem;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:1rem}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #ddd;padding:.4rem .6rem;text-align:left}
        .meta{color:#555;font-size:.9rem}
        .alerta{background:#fff3cd;padding:.75rem 1rem;border-radius:6px}
        form{display:flex;gap:.75rem;flex-wrap:wrap;align-items:end;margin-bottom:1rem}
        label{display:flex;flex-direction:column;font-size:.85rem;gap:.25rem}
        input{padding:.35rem .5rem}
        button{padding:.45rem .9rem;cursor:pointer}
    </style>
</head>
<body>
<div class="card">
    <h1>Cardápio do dia</h1>
    <p class="meta">Fonte: {{ $fonte }} · Escola: {{ $cardapio['escola'] ?? $escola }} · Itens: {{ $cardapio['total_itens'] ?? 0 }}</p>
    <form method="get">
        <label>Data <input type="date" name="data" value="{{ $data }}"></label>
        <label>Escola <input name="escola" value="{{ $escola }}"></label>
        <button type="submit">Filtrar</button>
    </form>
    @foreach(($cardapio['refeicoes'] ?? []) as $ref)
        <h2>{{ $ref['refeicao'] }}</h2>
        <ul>
            @foreach(($ref['itens'] ?? []) as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endforeach
</div>
@if(!empty($alertas))
<div class="card alerta">
    <h2>Restrições em conflito</h2>
    <ul>
        @foreach($alertas as $a)
            <li>Aluno {{ $a['aluno_id'] }} — {{ $a['restricao'] }}</li>
        @endforeach
    </ul>
</div>
@endif
@if(!empty($restricoes))
<div class="card">
    <h2>Restrições cadastradas</h2>
    <table>
        <thead><tr><th>Aluno</th><th>Restrição</th></tr></thead>
        <tbody>
        @foreach($restricoes as $r)
            <tr><td>{{ $r['aluno_id'] }}</td><td>{{ $r['restricao'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<p><a href="{{ url('/alimentacao-escolar') }}">← Voltar</a></p>
</body>
</html>
