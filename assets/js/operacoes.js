// ── Modal "Registrar Parada Não Programada" ───────────────────────────────────
(function () {
    const modal = document.getElementById('modalParada');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('parada-id-viagem').value = btn.dataset.idViagem;
        document.getElementById('parada-label-op').textContent =
            'Viagem #' + String(btn.dataset.idViagem).padStart(4, '0');
    });
})();

// ── Lógica do modal "Simular Desvio de Rota" ─────────────────────────────────
(function () {
    let _mapaDesvio          = null;
    let _origemMarker        = null;
    let _rotaLayer           = null;
    let _destinoCoords       = [];
    let _origemOriginalCoord = null;

    const modal = document.getElementById('modalDesvio');

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('desvio-id-rota').value    = btn.dataset.idRota;
        document.getElementById('desvio-id-viagem').value  = btn.dataset.idViagem;
        document.getElementById('desvio-label-op').textContent =
            'Operação #' + String(btn.dataset.idRota).padStart(4, '0');
        document.getElementById('btn-confirmar-desvio').disabled = true;
        document.getElementById('desvio-info').innerHTML    = '';
        document.getElementById('desvio-resumo').innerHTML  = '';
        document.getElementById('desvio-nova-distancia').value = '';
        document.getElementById('desvio-origem-nome').value   = '';
        _destinoCoords       = [];
        _origemMarker        = null;
        _rotaLayer           = null;
        _origemOriginalCoord = null;
    });

    modal.addEventListener('shown.bs.modal', async function () {
        if (_mapaDesvio) { _mapaDesvio.remove(); _mapaDesvio = null; }
        const mapEl = document.getElementById('mapa-desvio');
        mapEl.innerHTML = '';
        _mapaDesvio = L.map(mapEl).setView([-15.8, -47.9], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
        }).addTo(_mapaDesvio);

        const idRota     = document.getElementById('desvio-id-rota').value;
        const mapaDataEl = document.getElementById('mapa-data-' + idRota);
        const info       = document.getElementById('desvio-info');

        if (!mapaDataEl) {
            info.innerHTML = '<small class="text-muted"><i class="bi bi-cursor me-1"></i>Clique no mapa para definir a posição do veículo.</small>';
            _bindMapClick(info);
            return;
        }

        const dados    = JSON.parse(mapaDataEl.textContent);
        const entregas = dados.entregas || [];

        info.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><small class="text-muted">Carregando destinos no mapa...</small>';

        for (let i = 0; i < entregas.length; i++) {
            const ent = entregas[i];
            if (!ent.cidade) continue;
            if (i > 0) await _sleep(1100);
            const coord = await _geocodificar([ent.cidade, ent.estado, 'Brasil'].filter(Boolean).join(', '));
            if (coord) {
                _destinoCoords.push(coord);
                L.marker([coord.lat, coord.lon], { icon: _iconParada(_destinoCoords.length) })
                    .addTo(_mapaDesvio)
                    .bindPopup(`<strong>Destino ${_destinoCoords.length}</strong><br>${ent.cliente}<br><small>${ent.cidade}${ent.estado ? '/' + ent.estado : ''}</small>`);
            }
        }

        if (dados.origem && dados.origem.cidade) {
            if (entregas.length > 0) await _sleep(1100);
            const coordOrig = await _geocodificar(
                [dados.origem.cidade, dados.origem.estado, 'Brasil'].filter(Boolean).join(', ')
            );
            if (coordOrig) {
                _origemOriginalCoord = coordOrig;
                L.marker([coordOrig.lat, coordOrig.lon], { icon: _iconOrigem(dados.origem.nome || 'Origem') })
                    .addTo(_mapaDesvio)
                    .bindPopup(`<strong>Origem</strong><br>${dados.origem.nome || ''}<br><small>${dados.origem.cidade}</small>`);
            }
        }

        const todosCoords = [...(_origemOriginalCoord ? [[_origemOriginalCoord.lat, _origemOriginalCoord.lon]] : []),
                              ..._destinoCoords.map(c => [c.lat, c.lon])];
        if (todosCoords.length > 0) {
            _mapaDesvio.fitBounds(todosCoords, { padding: [40, 40] });
        }

        info.innerHTML = '<small class="text-muted"><i class="bi bi-cursor me-1"></i>Clique no mapa para definir a nova posição do veículo.</small>';
        _bindMapClick(info);
    });

    function _bindMapClick(info) {
        _mapaDesvio.on('click', async function (ev) {
            const { lat, lng } = ev.latlng;

            info.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><small class="text-muted">Identificando localização...</small>';
            document.getElementById('btn-confirmar-desvio').disabled = true;

            if (_origemMarker) _origemMarker.remove();
            _origemMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: '<div style="background:#fd7e14;color:#fff;border-radius:20px;padding:3px 8px;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4);white-space:nowrap">&#128652; Posição Atual</div>',
                    className: '', iconAnchor: [0, 14]
                })
            }).addTo(_mapaDesvio).bindPopup('Nova posição de origem').openPopup();

            if (_rotaLayer) { _rotaLayer.remove(); _rotaLayer = null; }

            let nomeLoc = lat.toFixed(4) + ', ' + lng.toFixed(4);
            try {
                const rUrl = 'https://nominatim.openstreetmap.org/reverse?' + new URLSearchParams(
                    { lat: lat, lon: lng, format: 'json', zoom: 10 }
                );
                const rd = await (await fetch(rUrl, { headers: { 'Accept-Language': 'pt-BR,pt;q=0.9' } })).json();
                if (rd.address) {
                    nomeLoc = [
                        rd.address.city || rd.address.town || rd.address.municipality || rd.address.county,
                        rd.address.state
                    ].filter(Boolean).join(', ');
                }
            } catch (_) {}

            document.getElementById('desvio-origem-nome').value = nomeLoc;

            if (_destinoCoords.length === 0) {
                document.getElementById('desvio-nova-distancia').value = '0';
                document.getElementById('desvio-resumo').innerHTML =
                    `<i class="bi bi-geo-alt-fill text-warning me-1"></i><strong>${nomeLoc}</strong> — sem destinos para calcular rota`;
                info.innerHTML = `<span class="badge bg-warning text-dark">${nomeLoc}</span>`;
                document.getElementById('btn-confirmar-desvio').disabled = false;
                return;
            }

            info.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><small class="text-muted">Calculando nova rota...</small>';

            const pontos = [
                ...(_origemOriginalCoord ? [_origemOriginalCoord] : []),
                { lat, lon: lng },
                ..._destinoCoords
            ];
            try {
                const wp  = pontos.map(p => `${p.lon},${p.lat}`).join(';');
                const res = await fetch(`https://router.project-osrm.org/route/v1/driving/${wp}?overview=full&geometries=geojson`);
                const rdata = await res.json();

                if (rdata.code !== 'Ok') throw new Error('osrm');

                const distKm = parseFloat((rdata.routes[0].distance / 1000).toFixed(1));
                _rotaLayer = L.geoJSON(rdata.routes[0].geometry, {
                    style: { color: '#fd7e14', weight: 4, opacity: 0.9 }
                }).addTo(_mapaDesvio);
                _mapaDesvio.fitBounds(L.geoJSON(rdata.routes[0].geometry).getBounds().pad(0.1));
                _setResultado(nomeLoc, distKm, info, false);

            } catch (_) {
                let soma = 0;
                if (_origemOriginalCoord) {
                    soma += _haversineKm(_origemOriginalCoord.lat, _origemOriginalCoord.lon, lat, lng);
                }
                let prev = { lat, lon: lng };
                for (const d of _destinoCoords) {
                    soma += _haversineKm(prev.lat, prev.lon, d.lat, d.lon);
                    prev = d;
                }
                _setResultado(nomeLoc, parseFloat(soma.toFixed(1)), info, true);
            }
        });
    }

    function _setResultado(nomeLoc, distKm, info, estimativa) {
        document.getElementById('desvio-nova-distancia').value = distKm;
        const tag = estimativa
            ? `<span class="badge bg-secondary">~${distKm} km (estimativa)</span>`
            : `<span class="badge bg-primary"><i class="bi bi-signpost-2 me-1"></i>${distKm} km</span>`;
        document.getElementById('desvio-resumo').innerHTML =
            `<i class="bi bi-geo-alt-fill text-warning me-1"></i><strong>${nomeLoc}</strong> &rarr; ${tag}`;
        info.innerHTML =
            `<div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="badge bg-warning text-dark"><i class="bi bi-geo-alt me-1"></i>${nomeLoc}</span>
                ${tag}
             </div>`;
        document.getElementById('btn-confirmar-desvio').disabled = false;
    }

    function _haversineKm(lat1, lon1, lat2, lon2) {
        const R    = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a    = Math.sin(dLat / 2) ** 2
                   + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                   * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }
})();
