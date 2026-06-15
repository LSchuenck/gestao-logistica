(function () {
    const viagensData = JSON.parse(document.getElementById('viagens-json').textContent);
    let viagemAtual = null;

    window.onViagemChange = function () {
        const id = parseInt(document.getElementById('sel-viagem').value);
        viagemAtual = viagensData.find(v => v.id === id) || null;
        const painel = document.getElementById('painel-viagem');
        const hiddenTransp = document.getElementById('input-id-transportadora');
        const displayTransp = document.getElementById('display-transportadora');

        if (!viagemAtual) {
            painel.style.display = 'none';
            hiddenTransp.value = '';
            displayTransp.innerHTML = '<span class="text-secondary fst-italic" style="font-size:.82rem">Selecione uma viagem acima</span>';
            return;
        }

        hiddenTransp.value = viagemAtual.id_transportadora;
        displayTransp.innerHTML =
            `<i class="bi bi-building me-1 text-muted"></i><strong>${viagemAtual.transportadora_nome}</strong>`;

        painel.style.display = 'block';
        document.getElementById('info-dist').textContent =
            viagemAtual.distancia > 0 ? viagemAtual.distancia.toLocaleString('pt-BR', {maximumFractionDigits:1}) : '—';
        document.getElementById('info-peso').textContent =
            viagemAtual.peso_total > 0 ? viagemAtual.peso_total.toLocaleString('pt-BR', {maximumFractionDigits:2}) : '—';
        document.getElementById('info-vol').textContent =
            viagemAtual.volume_total > 0 ? viagemAtual.volume_total.toLocaleString('pt-BR', {minimumFractionDigits:4, maximumFractionDigits:4}) : '—';

        calcularFrete();
    };

    window.calcularFrete = function () {
        if (!viagemAtual) return;
        const tarifaKm = parseFloat(document.getElementById('tarifa-km').value) || 0;
        const tarifaKg = parseFloat(document.getElementById('tarifa-kg').value) || 0;

        const dist     = viagemAtual.distancia;
        const pesoReal = viagemAtual.peso_total;
        const vol      = viagemAtual.volume_total;

        const pesoCubado = vol * 300;
        const pesoTaxado = Math.max(pesoReal, pesoCubado);
        const valorSugerido = (dist * tarifaKm) + (pesoTaxado * tarifaKg);

        const fmt = (n) => n.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('info-cubado').textContent   = fmt(pesoCubado) + ' kg';
        document.getElementById('info-taxado').textContent   = fmt(pesoTaxado) + ' kg';
        document.getElementById('info-sugerido').textContent = 'R$ ' + fmt(valorSugerido);
    };

    window.aplicarValorSugerido = function () {
        if (!viagemAtual) return;
        const tarifaKm = parseFloat(document.getElementById('tarifa-km').value) || 0;
        const tarifaKg = parseFloat(document.getElementById('tarifa-kg').value) || 0;
        const pesoCubado = viagemAtual.volume_total * 300;
        const pesoTaxado = Math.max(viagemAtual.peso_total, pesoCubado);
        const valor = (viagemAtual.distancia * tarifaKm) + (pesoTaxado * tarifaKg);
        document.getElementById('input-valor-frete').value = valor.toFixed(2);
    };

    document.getElementById('form-frete').addEventListener('submit', function (e) {
        if (!document.getElementById('input-id-transportadora').value) {
            e.preventDefault();
            document.getElementById('sel-viagem').focus();
            document.getElementById('sel-viagem').classList.add('is-invalid');
        }
    });
    document.getElementById('sel-viagem').addEventListener('change', function () {
        this.classList.remove('is-invalid');
    });
})();
