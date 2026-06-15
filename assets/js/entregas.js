(function () {
    // Todos os produtos do sistema (fallback quando nenhum armazém está selecionado)
    const allProdutos = JSON.parse(document.getElementById('produtos-json').textContent);
    let produtosData  = [...allProdutos]; // cópia mutável — atualizada via AJAX
    let rowIdx = 0;

    // ── Filtro dinâmico por armazém ──────────────────────────────────────────
    const selArmazem = document.getElementById('sel-armazem-form');
    if (selArmazem) {
        selArmazem.addEventListener('change', async function () {
            const idArmazem = this.value;

            // Limpa as linhas de produto existentes ao trocar de armazém
            document.getElementById('itens-tbody').innerHTML = '';
            document.getElementById('tabela-itens').style.display = 'none';
            document.getElementById('itens-vazio').style.display  = '';
            recalcTotais();

            if (!idArmazem) {
                produtosData = [...allProdutos];
                return;
            }

            try {
                const res  = await fetch(`entregas.php?ajax=produtos_armazem&id=${idArmazem}`);
                const data = await res.json();
                produtosData = data.map(p => ({
                    id:        parseInt(p.id_produto),
                    nome:      p.descricao,
                    peso:      parseFloat(p.peso   || 0),
                    volume:    parseFloat(p.volume  || 0),
                    disponivel: parseInt(p.quantidade_disponivel),
                }));
            } catch (_) {
                produtosData = [...allProdutos];
            }
        });
    }

    // ── Gera as <option> do select de produto ────────────────────────────────
    function buildOptions() {
        if (produtosData.length === 0) {
            return '<option value="" disabled>Nenhum produto em estoque neste armazém</option>';
        }
        let html = '<option value="">Selecione o produto...</option>';
        produtosData.forEach(p => {
            const nome   = p.nome.replace(/&/g,'&amp;').replace(/</g,'&lt;');
            const disp   = p.disponivel !== undefined ? ` (${p.disponivel} disp.)` : '';
            const maxVal = p.disponivel !== undefined ? ` data-max="${p.disponivel}"` : '';
            html += `<option value="${p.id}" data-peso="${p.peso}" data-vol="${p.volume}"${maxVal}>${nome}${disp}</option>`;
        });
        return html;
    }

    // ── Adiciona uma linha de produto à tabela ───────────────────────────────
    window.addProdutoLinha = function () {
        const idx   = rowIdx++;
        const tbody = document.getElementById('itens-tbody');
        const tr    = document.createElement('tr');
        tr.id = 'el-linha-' + idx;
        tr.innerHTML = `
            <td>
                <select name="produtos[${idx}][id_produto]" class="form-select form-select-sm sel-prod" required>
                    ${buildOptions()}
                </select>
            </td>
            <td>
                <input type="number" name="produtos[${idx}][quantidade]" min="1" value="1"
                       class="form-control form-control-sm inp-qtd" style="width:60px">
            </td>
            <td class="text-end align-middle small text-muted td-peso">—</td>
            <td class="text-end align-middle small text-muted td-vol">—</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-0 px-1" onclick="removerLinha(${idx})">
                    <i class="bi bi-x"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);

        const sel      = tr.querySelector('.sel-prod');
        const qtdInput = tr.querySelector('.inp-qtd');

        sel.addEventListener('change', function () {
            const opt    = this.selectedOptions[0];
            const maxVal = opt ? parseInt(opt.dataset.max) : NaN;
            if (!isNaN(maxVal)) {
                qtdInput.max = maxVal;
                if (parseInt(qtdInput.value) > maxVal) qtdInput.value = maxVal;
            } else {
                qtdInput.removeAttribute('max');
            }
            qtdInput.classList.remove('is-invalid');
            recalcTotais();
        });

        qtdInput.addEventListener('input', function () {
            const max = parseInt(this.max);
            if (!isNaN(max) && parseInt(this.value) > max) {
                this.value = max;
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
            recalcTotais();
        });

        document.getElementById('tabela-itens').style.display = '';
        document.getElementById('itens-vazio').style.display  = 'none';
        recalcTotais();
    };

    // ── Remove uma linha de produto ──────────────────────────────────────────
    window.removerLinha = function (idx) {
        const row = document.getElementById('el-linha-' + idx);
        if (row) row.remove();
        if (!document.getElementById('itens-tbody').children.length) {
            document.getElementById('tabela-itens').style.display = 'none';
            document.getElementById('itens-vazio').style.display  = '';
        }
        recalcTotais();
    };

    // ── Recalcula totais de peso e volume ────────────────────────────────────
    window.recalcTotais = function () {
        let peso = 0, vol = 0;
        document.querySelectorAll('#itens-tbody tr').forEach(tr => {
            const sel      = tr.querySelector('.sel-prod');
            const qtdInput = tr.querySelector('.inp-qtd');
            const qtd      = Math.max(1, parseInt(qtdInput.value) || 1);
            const opt      = sel.selectedOptions[0];
            if (opt && opt.value) {
                const lPeso = parseFloat(opt.dataset.peso || 0) * qtd;
                const lVol  = parseFloat(opt.dataset.vol  || 0) * qtd;
                peso += lPeso;
                vol  += lVol;
                tr.querySelector('.td-peso').textContent = lPeso.toFixed(2) + ' kg';
                tr.querySelector('.td-vol').textContent  = lVol.toFixed(4)  + ' m³';
            } else {
                tr.querySelector('.td-peso').textContent = '—';
                tr.querySelector('.td-vol').textContent  = '—';
            }
        });
        document.getElementById('total-peso-display').textContent = peso.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('total-vol-display').textContent  = vol.toLocaleString('pt-BR',  {minimumFractionDigits:4, maximumFractionDigits:4});
        document.getElementById('input-peso-total').value  = peso.toFixed(2);
        document.getElementById('input-vol-total').value   = vol.toFixed(4);
    };

    // ── Validação no submit ──────────────────────────────────────────────────
    document.getElementById('form-nova-entrega').addEventListener('submit', function (e) {
        let ok = true;
        document.querySelectorAll('#itens-tbody tr').forEach(tr => {
            const sel      = tr.querySelector('.sel-prod');
            const qtdInput = tr.querySelector('.inp-qtd');
            if (!sel.value) { sel.classList.add('is-invalid'); ok = false; }
            else sel.classList.remove('is-invalid');

            const max = parseInt(qtdInput.max);
            if (!isNaN(max) && parseInt(qtdInput.value) > max) {
                qtdInput.classList.add('is-invalid');
                ok = false;
            }
        });
        if (!ok) {
            e.preventDefault();
            alert('Verifique os campos destacados: selecione o produto e não ultrapasse a quantidade disponível em estoque.');
        }
    });
})();
