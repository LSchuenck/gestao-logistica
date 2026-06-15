// ── Dados injetados pelo PHP via data attributes ──────────────────────────
const _appData              = document.getElementById('app-data');
const motoristasData        = JSON.parse(_appData.dataset.motoristas       || '[]');
const veiculosData          = JSON.parse(_appData.dataset.veiculos         || '[]');
const transportadorasData   = JSON.parse(_appData.dataset.transportadoras  || '[]');
const entregasPendentesData = JSON.parse(_appData.dataset.entregasPendentes || '[]');

// ── Cascade transportadora → motorista / veículo + lista de entregas ──────
document.getElementById('sel-transportadora').addEventListener('change', function () {
    const tid  = this.value;
    const selM = document.getElementById('sel-motorista');
    const selV = document.getElementById('sel-veiculo');
    const section = document.getElementById('entregas-form-section');

    selM.innerHTML = '<option value="">Selecione...</option>';
    selV.innerHTML = '<option value="">Selecione...</option>';
    section.style.display = 'none';

    if (!tid) {
        selM.disabled = true;
        selV.disabled = true;
        return;
    }

    const mots = motoristasData.filter(m => String(m.id_transportadora) === tid);
    const vets = veiculosData.filter(v => String(v.id_transportadora) === tid);

    mots.forEach(m => selM.add(new Option(m.nome, m.id_motorista)));
    vets.forEach(v => {
        const cap = v.capacidade_carga
            ? ` (${parseFloat(v.capacidade_carga).toLocaleString('pt-BR', { maximumFractionDigits: 0 })} kg)`
            : '';
        selV.add(new Option(`${v.placa} — ${v.tipo_veiculo}${cap}`, v.id_veiculo));
    });

    selM.disabled = mots.length === 0;
    selV.disabled = vets.length === 0;

    // Popula lista de entregas pendentes com cidade
    const lista = document.getElementById('lista-entregas-form');
    lista.innerHTML = '';
    const comCidade = entregasPendentesData.filter(e => e.cidade);

    if (comCidade.length === 0) {
        lista.innerHTML = '<span class="text-muted fst-italic">Nenhuma entrega pendente com endereço</span>';
    } else {
        comCidade.forEach(e => {
            const data = e.data_prevista
                ? new Date(e.data_prevista).toLocaleDateString('pt-BR', { timeZone: 'UTC' })
                : '';
            const armLabel = e.armazem_nome
                ? `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" style="font-size:.68rem">${e.armazem_nome}</span>`
                : '';
            lista.innerHTML += `
                <label class="d-flex align-items-center gap-2 py-1 border-bottom" style="cursor:pointer">
                    <input type="checkbox" name="id_entregas[]" value="${e.id_entrega}"
                           class="form-check-input flex-shrink-0 mt-0">
                    <span class="font-monospace text-muted" style="font-size:.73rem">#${String(e.id_entrega).padStart(4,'0')}</span>
                    <span class="flex-grow-1">${e.cliente}${armLabel}</span>
                    <span class="text-muted small">${e.cidade}${e.estado ? '/'+e.estado : ''}${data ? ' · '+data : ''}</span>
                </label>`;
        });
    }
    section.style.display = 'block';
});

// ── Mapa + Geocoding (Nominatim) + Rota (OSRM) ───────────────────────────
const _mapas = {};
let   _mapaForm = null;

async function _geocodificar(query) {
    const url = 'https://nominatim.openstreetmap.org/search?' + new URLSearchParams({
        q: query, format: 'json', limit: '1', countrycodes: 'br'
    });
    const r = await fetch(url, { headers: { 'Accept-Language': 'pt-BR,pt;q=0.9' } });
    const d = await r.json();
    return d[0] ? { lat: parseFloat(d[0].lat), lon: parseFloat(d[0].lon) } : null;
}

function _sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function _criarMapa(el) {
    const map = L.map(el).setView([-15.8, -47.9], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
    }).addTo(map);
    return map;
}

function _iconOrigem(nome) {
    return L.divIcon({
        html: `<div style="background:#198754;color:#fff;border-radius:20px;padding:3px 8px;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4);white-space:nowrap">&#128230; ${nome}</div>`,
        className: '', iconAnchor: [0, 14]
    });
}

function _iconParada(num) {
    return L.divIcon({
        html: `<div class="stop-marker">${num}</div>`,
        className: '', iconAnchor: [12, 12], iconSize: [24, 24]
    });
}

async function _desenharRota(map, todosPontos, info, distKmCallback) {
    if (todosPontos.length < 2) {
        if (todosPontos.length === 1) map.setView([todosPontos[0].lat, todosPontos[0].lon], 10);
        return;
    }
    try {
        const wp  = todosPontos.map(p => `${p.lon},${p.lat}`).join(';');
        const res = await fetch(`https://router.project-osrm.org/route/v1/driving/${wp}?overview=full&geometries=geojson`);
        const data = await res.json();
        if (data.code === 'Ok') {
            const distKm = (data.routes[0].distance / 1000).toFixed(1);
            L.geoJSON(data.routes[0].geometry, {
                style: { color: '#0d6efd', weight: 4, opacity: 0.85 }
            }).addTo(map);
            map.fitBounds(L.geoJSON(data.routes[0].geometry).getBounds().pad(0.15));
            if (distKmCallback) distKmCallback(distKm);
            return distKm;
        }
    } catch (e) {
        info.innerHTML += '<p class="text-danger small mt-2 mb-0"><i class="bi bi-wifi-off me-1"></i>Sem conexão com o servidor de rotas.</p>';
    }
    return null;
}

// ── Calcular trajeto no formulário de planejamento ────────────────────────
async function calcularTrajetoForm() {
    const btn       = document.getElementById('btn-calc-form');
    const container = document.getElementById('mapa-form-container');
    const mapEl     = document.getElementById('mapa-form');
    const info      = document.getElementById('mapa-form-info');
    const inputDist = document.getElementById('input-distancia-form');

    const tid = document.getElementById('sel-transportadora').value;
    if (!tid) { alert('Selecione a transportadora primeiro.'); return; }

    const transp   = transportadorasData.find(t => String(t.id_transportadora) === tid);
    const checked  = document.querySelectorAll('#lista-entregas-form input[type=checkbox]:checked');
    const selIds   = Array.from(checked).map(c => c.value);
    const entregas = entregasPendentesData.filter(e => selIds.includes(String(e.id_entrega)));

    // Usa armazém da primeira entrega com armazém definido como origem
    const entComArmazem = entregas.find(e => e.armazem_cidade);
    const origem = entComArmazem
        ? { nome: entComArmazem.armazem_nome, cidade: entComArmazem.armazem_cidade, estado: entComArmazem.armazem_estado || '' }
        : (transp ? { nome: transp.nome_fantasia, cidade: transp.cidade || '', estado: transp.estado || '' } : null);

    container.style.display = 'block';
    info.innerHTML = '';
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span>Iniciando...';

    if (_mapaForm) { _mapaForm.remove(); _mapaForm = null; }
    mapEl.innerHTML = '';
    _mapaForm = _criarMapa(mapEl);

    // Geocodifica origem
    let origemCoord = null;
    if (origem && origem.cidade) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Localizando origem...';
        origemCoord = await _geocodificar([origem.cidade, origem.estado, 'Brasil'].filter(Boolean).join(', '));
        if (origemCoord) {
            L.marker([origemCoord.lat, origemCoord.lon], { icon: _iconOrigem(origem.nome) })
                .addTo(_mapaForm)
                .bindPopup(`<strong>Origem</strong><br>${origem.nome}<br><small>${origem.cidade}${origem.estado ? '/'+origem.estado : ''}</small>`);
        } else {
            info.innerHTML += `<span class="badge bg-warning text-dark me-1">⚠ Origem (${origem.nome}): cidade não encontrada</span>`;
        }
        if (entregas.length > 0) await _sleep(1100);
    }

    // Geocodifica entregas selecionadas
    const paradas = [];
    for (let i = 0; i < entregas.length; i++) {
        const e = entregas[i];
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Localizando parada ${i+1}/${entregas.length}...`;
        const coord = await _geocodificar([e.cidade, e.estado, 'Brasil'].filter(Boolean).join(', '));
        if (coord) {
            paradas.push(coord);
            L.marker([coord.lat, coord.lon], { icon: _iconParada(paradas.length) })
                .addTo(_mapaForm)
                .bindPopup(`<strong>Parada ${paradas.length}</strong><br>${e.cliente}<br><small>${e.cidade}${e.estado ? '/'+e.estado : ''}</small>`);
        } else {
            info.innerHTML += `<span class="badge bg-warning text-dark me-1">⚠ Não encontrado: ${e.cliente}</span>`;
        }
        if (i < entregas.length - 1) await _sleep(1100);
    }

    const todosPontos = [];
    if (origemCoord) todosPontos.push(origemCoord);
    todosPontos.push(...paradas);

    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Calculando rota...';
    const distKm = await _desenharRota(_mapaForm, todosPontos, info, (km) => {
        inputDist.value = km;
        info.innerHTML += `
            <div class="d-flex align-items-center gap-2 mt-2 p-2 bg-light rounded-3">
                <i class="bi bi-signpost-2 text-primary"></i>
                <span class="fw-bold">${km} km</span>
                <span class="text-muted small">· ${paradas.length} parada(s) — aplicado no campo abaixo</span>
            </div>`;
    });

    btn.disabled  = false;
    btn.innerHTML = '<i class="bi bi-map me-1"></i>Recalcular';
}

// ── Visualizar trajeto nos cards (somente leitura) ────────────────────────
async function calcularTrajeto(rotaId) {
    const btn       = document.getElementById('btn-mapa-' + rotaId);
    const container = document.getElementById('mapa-container-' + rotaId);
    const mapEl     = document.getElementById('mapa-' + rotaId);
    const info      = document.getElementById('mapa-info-' + rotaId);
    const dados     = JSON.parse(document.getElementById('mapa-data-' + rotaId).textContent);
    const origem    = dados.origem;
    const entregas  = dados.entregas;

    container.style.display = 'block';
    info.innerHTML = '';
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span>Iniciando...';

    if (_mapas[rotaId]) { _mapas[rotaId].remove(); _mapas[rotaId] = null; }
    mapEl.innerHTML = '';
    const map = _criarMapa(mapEl);
    _mapas[rotaId] = map;

    // Geocodifica origem
    let origemCoord = null;
    if (origem.cidade) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Localizando origem...';
        origemCoord = await _geocodificar([origem.cidade, origem.estado, 'Brasil'].filter(Boolean).join(', '));
        if (origemCoord) {
            L.marker([origemCoord.lat, origemCoord.lon], { icon: _iconOrigem(origem.nome || 'Origem') })
                .addTo(map)
                .bindPopup(`<strong>Origem</strong><br>${origem.nome}<br><small>${origem.cidade}${origem.estado ? '/'+origem.estado : ''}</small>`);
        } else {
            info.innerHTML += `<span class="badge bg-warning text-dark me-1">⚠ Origem: cidade não encontrada</span>`;
        }
        await _sleep(1100);
    }

    // Geocodifica entregas
    const paradas = [];
    for (let i = 0; i < entregas.length; i++) {
        const e = entregas[i];
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Localizando parada ${i+1}/${entregas.length}...`;
        if (!e.cidade) {
            info.innerHTML += `<span class="badge bg-warning text-dark me-1">⚠ ${e.cliente}: sem cidade</span>`;
            if (i < entregas.length - 1) await _sleep(1100);
            continue;
        }
        const coord = await _geocodificar([e.cidade, e.estado, 'Brasil'].filter(Boolean).join(', '));
        if (coord) {
            paradas.push(coord);
            L.marker([coord.lat, coord.lon], { icon: _iconParada(paradas.length) })
                .addTo(map)
                .bindPopup(`<strong>Parada ${paradas.length}</strong><br>${e.cliente}<br><small>${e.cidade}${e.estado ? '/'+e.estado : ''}</small>`);
        } else {
            info.innerHTML += `<span class="badge bg-warning text-dark me-1">⚠ Não encontrado: ${e.cliente}</span>`;
        }
        if (i < entregas.length - 1) await _sleep(1100);
    }

    const todosPontos = [];
    if (origemCoord) todosPontos.push(origemCoord);
    todosPontos.push(...paradas);

    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Calculando rota...';
    const distKm = await _desenharRota(map, todosPontos, info, (km) => {
        info.innerHTML += `
            <div class="d-flex align-items-center gap-2 mt-2 p-2 bg-light rounded-3">
                <i class="bi bi-signpost-2 text-primary"></i>
                <span class="fw-bold">${km} km</span>
                <span class="text-muted small">· ${paradas.length} parada(s)</span>
            </div>`;
    });

    btn.disabled  = false;
    btn.innerHTML = '<i class="bi bi-map me-1"></i>Recalcular';
}
