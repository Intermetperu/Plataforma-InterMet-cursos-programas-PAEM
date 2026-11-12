// «Registrar pago»: cursos elegidos (agregar / quitar) y total en dólares (el monto se completa solo).
export function initRegistroPago() {
    const form = document.querySelector('[data-registro-pago]');
    if (!form) return;

    const select = form.querySelector('[data-agregar]');
    const lineas = form.querySelector('[data-lineas]');
    const resumen = form.querySelector('[data-resumen]');
    const ocultos = form.querySelector('[data-elegidos]');
    const sinCursos = form.querySelector('[data-sin-cursos]');
    const monto = form.querySelector('[data-monto]');
    const usd = (n) => 'USD ' + (Number.isInteger(n) ? n.toLocaleString('en-US') : n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

    const opcion = (id) => select.querySelector(`option[value="${id}"]`);
    let elegidos = [...ocultos.querySelectorAll('input')].map((i) => i.dataset.id || i.value).filter((id) => opcion(id));
    let total = 0;

    function pintar() {
        lineas.innerHTML = '';
        resumen.innerHTML = '';
        ocultos.innerHTML = '';
        total = 0;
        elegidos.forEach((id) => {
            const o = opcion(id);
            const precio = Number(o.dataset.precio);
            total += precio;
            const li = document.createElement('li');
            li.className = 'im-linea';
            li.innerHTML = '<span class="im-linea-num"></span><span class="im-linea-titulo"></span><span class="im-linea-precio"></span><button type="button" aria-label="Quitar"><i class="bx bx-trash"></i></button>';
            li.querySelector('.im-linea-num').textContent = o.dataset.numero;
            li.querySelector('.im-linea-titulo').textContent = o.dataset.titulo;
            li.querySelector('.im-linea-precio').textContent = usd(precio);
            li.querySelector('button').setAttribute('aria-label', 'Quitar ' + o.dataset.titulo);
            li.querySelector('button').addEventListener('click', () => { elegidos = elegidos.filter((x) => x !== id); pintar(); });
            lineas.appendChild(li);

            const r = document.createElement('li');
            r.innerHTML = '<span></span><b></b>';
            r.querySelector('span').textContent = `${o.dataset.numero} ${o.dataset.titulo}`.slice(0, 70);
            r.querySelector('b').textContent = usd(precio);
            resumen.appendChild(r);

            const h = document.createElement('input');
            // «p12» = programa N° 12 · «345» = curso
            h.type = 'hidden'; h.name = id.startsWith('p') ? 'programas[]' : 'cursos[]'; h.value = id.replace(/^p/, '');
            ocultos.appendChild(h);
            o.disabled = true;
        });
        select.querySelectorAll('option[value]').forEach((o) => { if (o.value && !elegidos.includes(o.value)) o.disabled = false; });
        sinCursos.hidden = elegidos.length > 0;
        total = Math.round(total * 100) / 100;
        form.querySelector('[data-total]').textContent = usd(total);
        sugerir();
    }

    function agregar() {
        const id = select.value;
        if (!id || elegidos.includes(id)) return;
        elegidos.push(id);
        select.value = '';
        pintar();
    }
    select.addEventListener('change', agregar);
    form.querySelector('[data-agregar-btn]').addEventListener('click', agregar);

    // Monto sugerido: el total (si el cliente no lo cambió a mano)
    let tocado = Boolean(monto.value);
    monto.addEventListener('input', () => { tocado = true; });
    function sugerir(forzar = false) {
        if ((tocado && !forzar) || !total) return;
        monto.value = total.toFixed(2);
    }
    form.querySelector('[data-usar-total]').addEventListener('click', () => { tocado = false; sugerir(true); });

    form.addEventListener('submit', (e) => {
        if (!elegidos.length) {
            e.preventDefault();
            window.alertaSW?.('Elija al menos un curso o programa', 'warning');
            select.focus();
        }
    });

    pintar();
}
