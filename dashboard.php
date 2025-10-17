<?php
// Nueva página de Dashboard (solo ADMIN) con métricas en vivo
require __DIR__ . '/api/config.php';
$u = current_user();
if(!$u || ($u['rol'] ?? '') !== 'admin'){
    header('Location: index.html?unauthorized=1');
    exit;
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard | Calzado Oxlaj</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js" crossorigin="anonymous"></script>
    <style>
        :root{ --bg:#0D1B2A; --panel:#0f172a; --muted:#64748b; --card:#0b1222; --accent:#22c55e; --accent2:#3b82f6; --accent3:#f59e0b; }
        *{box-sizing:border-box}
        body{margin:0;font-family:Poppins,system-ui,Segoe UI,Arial,sans-serif;background:linear-gradient(145deg,#0b1222,#0D1B2A 50%,#111827);color:#e5e7eb;min-height:100vh}
        .container{max-width:1200px;margin:0 auto;padding:22px}
        header{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .title{font-weight:700;font-size:1.5rem}
        .sub{font-size:.8rem;color:#9ca3af}
        .toolbar{display:flex;gap:8px;flex-wrap:wrap}
        .btn{background:#1f2937;color:#fff;border:1px solid #334155;border-radius:10px;padding:8px 12px;cursor:pointer}
        .btn:hover{filter:brightness(1.1)}
        .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-top:16px}
        .card{background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:16px}
        .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        .kpi .label{font-size:.75rem;color:#a1a1aa}
        .kpi .val{font-size:1.6rem;font-weight:700;margin-top:6px}
        .col-6{grid-column:span 6}
        .col-12{grid-column:span 12}
        .charts{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-top:8px}
        .chart-card{grid-column:span 6}
        .table{width:100%;border-collapse:collapse;font-size:.9rem}
        .table th,.table td{border-top:1px solid rgba(255,255,255,.08);padding:10px;text-align:left}
        .muted{color:#9ca3af}
        .row{display:flex;gap:10px;flex-wrap:wrap}
        input,select{background:#111827;color:#e5e7eb;border:1px solid #334155;border-radius:10px;padding:8px 12px}
        .badge{display:inline-block;background:#1f2937;color:#e5e7eb;border:1px solid #334155;border-radius:999px;padding:2px 8px;font-size:.75rem}
        @media (max-width: 900px){ .kpis{grid-template-columns:1fr 1fr} .chart-card{grid-column:span 12} .col-6{grid-column:span 12} }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <div class="title">Panel de control</div>
                <div class="sub">Bienvenido, <?php echo htmlspecialchars($u['nombre'] ?? 'Admin'); ?> · <span id="lastUpdated" class="badge">—</span></div>
            </div>
            <div class="toolbar">
                <a class="btn" href="index.html">⟵ Volver</a>
                <button id="refreshBtn" class="btn">🔄 Actualizar</button>
            </div>
        </header>

        <section class="card" style="margin-top:14px">
            <div class="row">
                <label>Desde <input id="from" type="date"></label>
                <label>Hasta <input id="to" type="date"></label>
                <button id="applyDates" class="btn">Aplicar</button>
                <span class="muted" id="statusText"></span>
            </div>
        </section>

        <section class="kpis" id="kpis">
            <div class="card kpi">
                <div class="label">Ventas (ingresos)</div>
                <div class="val" id="kpiIngresos">Q0.00</div>
                <div class="muted" id="kpiPedidos">0 pedidos · 0 ítems</div>
            </div>
                <div class="card kpi">
                    <div class="label">Compras</div>
                    <div class="val" id="kpiCompras">Q0.00</div>
                    <div class="muted" id="kpiComprasInfo">—</div>
                </div>
            <div class="card kpi">
                <div class="label">Carrito</div>
                <div class="val" id="kpiCartTotal">Q0.00</div>
                <div class="muted" id="kpiCartInfo">0 líneas · 0 artículos</div>
            </div>
            <div class="card kpi">
                <div class="label">Productos</div>
                <div class="val" id="kpiProdCount">0</div>
                <div class="muted" id="kpiProdInfo">—</div>
            </div>
            <div class="card kpi">
                <div class="label">Usuarios</div>
                <div class="val" id="kpiUsers">0</div>
                <div class="muted" id="kpiUsersInfo">—</div>
            </div>
        </section>

        <section class="charts">
            <div class="card chart-card">
                <h3 style="margin:4px 0 10px">Ingresos por mes</h3>
                <div style="height:300px"><canvas id="chartVentasMes"></canvas></div>
            </div>
            <div class="card chart-card">
                    <h3 style="margin:4px 0 10px">Compras por mes</h3>
                    <div style="height:300px"><canvas id="chartComprasMes"></canvas></div>
                </div>
                <div class="card chart-card">
                <h3 style="margin:4px 0 10px">Top productos por ingresos</h3>
                <div style="height:300px"><canvas id="chartTopProductos"></canvas></div>
            </div>
        </section>

        <section class="grid" style="margin-top:16px">
            <div class="card col-6">
                <h3 style="margin:0 0 10px">Tallas en carrito</h3>
                <div style="height:260px"><canvas id="chartTallas"></canvas></div>
            </div>
            <div class="card col-6">
                <h3 style="margin:0 0 10px">Colores en carrito</h3>
                <div style="height:260px"><canvas id="chartColores"></canvas></div>
            </div>
            <div class="card col-12">
                <h3 style="margin:0 0 10px">Favoritos (Top 10)</h3>
                <div style="overflow:auto">
                    <table class="table" id="favTable">
                        <thead><tr><th>ID</th><th>Producto</th><th>Favoritos</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <script>
        const fmtQ = new Intl.NumberFormat('es-GT', { style:'currency', currency:'GTQ' });
        const $ = (s, r=document)=>r.querySelector(s);
        const charts = {};
        function setText(id, v){ const el = document.getElementById(id); if(el) el.textContent = v; }
        function chart(id, type, data, options={}){
            const ctx = document.getElementById(id);
            if(!ctx) return;
            if(charts[id]) charts[id].destroy();
            charts[id] = new Chart(ctx, { type, data, options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } }, scales: { x:{ ticks:{color:'#cbd5e1'} }, y:{ ticks:{color:'#cbd5e1'} } }, ...options } });
        }
        function qs(obj){ return Object.entries(obj).filter(([,v])=>v).map(([k,v])=>`${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&'); }
        async function load(){
            const params = { from: $('#from')?.value || '', to: $('#to')?.value || '' };
            const url = 'api/admin_metrics.php' + (params.from||params.to ? ('?' + qs(params)) : '');
            $('#statusText').textContent = 'Cargando…';
            const res = await fetch(url, { credentials:'include' });
            if(!res.ok){ throw new Error('HTTP '+res.status); }
            const data = await res.json();
            $('#statusText').textContent = '';
            document.getElementById('lastUpdated').textContent = new Date().toLocaleString('es-GT');
            render(data);
        }
        function render(d){
            // KPIs ventas
            setText('kpiIngresos', fmtQ.format(d.ventas?.ingresos||0));
            setText('kpiPedidos', `${d.ventas?.pedidos||0} pedidos · ${d.ventas?.items||0} ítems`);
                    // Compras
                    if(d.compras){
                        setText('kpiCompras', fmtQ.format(d.compras.total||0));
                        setText('kpiComprasInfo', `${(d.compras.movimientos||0)} compras`);
                    }
            // KPIs carrito
            setText('kpiCartTotal', fmtQ.format(d.cart?.total||0));
            setText('kpiCartInfo', `${d.cart?.lines||0} líneas · ${d.cart?.items||0} artículos`);
            // Productos
            setText('kpiProdCount', (d.products?.count||0));
            setText('kpiProdInfo', `${(d.products?.with_image||0)} con imagen · ${(d.products?.without_img||0)} sin imagen · Precio prom: ${fmtQ.format(d.products?.avg_price||0)}`);
            // Usuarios
            setText('kpiUsers', (d.users?.count||0));
            setText('kpiUsersInfo', `${(d.users?.admins||0)} admin · ${(d.users?.clients||0)} clientes`);

            // Linea ingresos por mes
            const pm = Array.isArray(d.ventas?.por_mes) ? d.ventas.por_mes : [];
            chart('chartVentasMes', 'line', {
                labels: pm.map(x=>x.ym),
                datasets:[{ label:'Ingresos', data: pm.map(x=>x.ingresos), borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.2)', tension:.3 }]
            });
                    // Compras por mes
                    const cm = Array.isArray(d.compras?.por_mes) ? d.compras.por_mes : [];
                    chart('chartComprasMes', 'bar', {
                        labels: cm.map(x=>x.ym),
                        datasets:[{ label:'Compras', data: cm.map(x=>x.total), backgroundColor:'#f59e0b' }]
                    });
            // Barras top productos
            const tp = Array.isArray(d.ventas?.top_products) ? d.ventas.top_products : [];
            chart('chartTopProductos', 'bar', {
                labels: tp.map(x=>x.title),
                datasets:[{ label:'Ingresos', data: tp.map(x=>x.ingresos), backgroundColor:'#3b82f6' }]
            }, { indexAxis:'y' });
            // Dona tallas carrito
            const tallas = Array.isArray(d.cart?.by_size) ? d.cart.by_size : [];
            chart('chartTallas', 'doughnut', {
                labels: tallas.map(x=>x.label||'-'),
                datasets:[{ data: tallas.map(x=>x.qty), backgroundColor:['#22c55e','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#14b8a6','#eab308'] }]
            });
            // Dona colores carrito
            const colores = Array.isArray(d.cart?.by_color) ? d.cart.by_color : [];
            chart('chartColores', 'doughnut', {
                labels: colores.map(x=>x.label||'-'),
                datasets:[{ data: colores.map(x=>x.qty), backgroundColor:['#1f2937','#b45309'] }]
            });
            // Tabla favoritos top
            const tbody = document.querySelector('#favTable tbody');
            if (tbody){
                const rows = (Array.isArray(d.favorites?.top_products)?d.favorites.top_products:[]).map(x=>`<tr><td>${x.id}</td><td>${x.title}</td><td>${x.count}</td></tr>`).join('');
                tbody.innerHTML = rows || '<tr><td colspan="3" class="muted">Sin datos</td></tr>';
            }
        }
        document.getElementById('refreshBtn').addEventListener('click', load);
        document.getElementById('applyDates').addEventListener('click', load);
        load().catch(e=>{ document.getElementById('statusText').textContent = 'Error cargando métricas'; console.error(e); });
    </script>
</body>
</html>
