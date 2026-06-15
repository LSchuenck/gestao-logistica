function filtrarAlertas(tipo, btn) {
    document.querySelectorAll('.alerta-item').forEach(el => {
        el.style.display = (tipo === 'TODOS' || el.dataset.tipo === tipo) ? '' : 'none';
    });
    document.querySelectorAll('[onclick^="filtrarAlertas"]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
