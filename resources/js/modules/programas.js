// Programas de alta especialización: carrusel de tarjetas, galería de fotos y «Agregar al carrito» sin recargar.

export function initProgramas() {
    // ---------- Carrusel (flechas) ----------
    document.querySelectorAll('[data-carrusel]').forEach((carrusel) => {
        const seccion = carrusel.closest('section') || document;
        const ant = seccion.querySelector('[data-carrusel-ant]');
        const sig = seccion.querySelector('[data-carrusel-sig]');
        const paso = () => {
            const item = carrusel.querySelector('.im-programas-item');
            const gap = parseFloat(getComputedStyle(carrusel).columnGap) || 24;
            const visibles = Math.max(1, Math.round(carrusel.clientWidth / ((item?.offsetWidth || 300) + gap)));
            return visibles * ((item?.offsetWidth || 300) + gap);
        };
        const actualizar = () => {
            if (ant) ant.disabled = carrusel.scrollLeft <= 4;
            if (sig) sig.disabled = carrusel.scrollLeft + carrusel.clientWidth >= carrusel.scrollWidth - 4;
        };
        ant?.addEventListener('click', () => carrusel.scrollBy({ left: -paso(), behavior: 'smooth' }));
        sig?.addEventListener('click', () => carrusel.scrollBy({ left: paso(), behavior: 'smooth' }));
        carrusel.addEventListener('scroll', actualizar, { passive: true });
        window.addEventListener('resize', actualizar);
        actualizar();
    });

    // ---------- Galería del programa ----------
    document.querySelectorAll('[data-galeria]').forEach((g) => {
        const grande = g.querySelector('[data-foto-grande]');
        const fondo = g.querySelector('[data-foto-fondo]');
        g.querySelectorAll('[data-foto]').forEach((b) => {
            const mostrar = () => {
                g.querySelectorAll('[data-foto]').forEach((x) => x.classList.toggle('activa', x === b));
                if (grande) grande.src = b.dataset.foto;
                if (fondo) fondo.style.backgroundImage = `url('${b.dataset.foto}')`;
            };
            b.addEventListener('click', mostrar);
            b.addEventListener('mouseenter', mostrar);
        });
    });

    // ---------- Agregar al carrito ----------
    let toast;
    const avisar = (mensaje) => {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'im-toast-carrito';
            toast.setAttribute('role', 'status');
            toast.innerHTML = '<i class="bx bx-check-circle"></i><span></span><a href="/carrito">Ver carrito</a>';
            document.body.appendChild(toast);
        }
        toast.querySelector('span').textContent = mensaje;
        toast.classList.add('visible');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('visible'), 4500);
    };
    const contador = (n) => {
        document.querySelectorAll('[data-carrito-num]').forEach((el) => { el.textContent = n; el.hidden = !n; });
        document.querySelectorAll('[data-carrito-btn]').forEach((el) => {
            el.classList.remove('salta'); void el.offsetWidth; el.classList.add('salta');
        });
    };

    document.querySelectorAll('[data-agregar-carrito]').forEach((form) => form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const boton = form.querySelector('button');
        if (boton.classList.contains('agregado')) { window.location.href = '/carrito'; return; }
        boton.disabled = true;
        try {
            const r = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
            });
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();
            contador(d.cantidad);
            boton.classList.add('agregado');
            boton.innerHTML = `<i class="bx bx-check"></i> <span>${boton.dataset.textoAgregado || 'En tu carrito'}</span>`;
            avisar(d.mensaje);
        } catch {
            form.submit();
        } finally {
            boton.disabled = false;
        }
    }));
}

// ---------- Página de todos los programas: buscar, filtrar por área y ordenar ----------
export function initListaProgramas() {
    const grid = document.querySelector('[data-paem-grid]');
    if (!grid) return;
    const items = [...grid.querySelectorAll('[data-paem-item]')];
    const buscar = document.querySelector('[data-paem-buscar]');
    const limpiar = document.querySelector('[data-paem-limpiar]');
    const orden = document.querySelector('[data-paem-orden]');
    const total = document.querySelector('[data-paem-total]');
    const vacio = document.querySelector('[data-paem-vacio]');
    const areas = [...document.querySelectorAll('[data-paem-area]')];
    const normal = (t) => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    let area = new URLSearchParams(location.search).get('area') || '';

    function aplicar() {
        const palabras = normal(buscar.value).split(/\s+/).filter((p) => p.length > 1);
        let n = 0;
        items.forEach((it) => {
            const ok = (!area || it.dataset.area === area) && palabras.every((p) => it.dataset.texto.includes(p));
            it.hidden = !ok;
            if (ok) n++;
        });
        const campo = { 'precio-asc': 'precio', 'precio-desc': 'precio', cursos: 'cursos', ahorro: 'ahorro' }[orden.value] || 'orden';
        const signo = ['precio-desc', 'cursos', 'ahorro'].includes(orden.value) ? -1 : 1;
        [...items].sort((a, b) => signo * (Number(a.dataset[campo]) - Number(b.dataset[campo])) || a.dataset.orden - b.dataset.orden)
            .forEach((it) => grid.appendChild(it));
        total.textContent = `${n} ${n === 1 ? 'programa' : 'programas'}`;
        vacio.hidden = n > 0;
        vacio.querySelector('[data-paem-palabra]').textContent = buscar.value.trim();
        limpiar.hidden = !buscar.value;
        areas.forEach((b) => b.classList.toggle('activa', b.dataset.paemArea === area));
    }

    buscar.addEventListener('input', aplicar);
    document.querySelector('[data-paem-buscar-form]').addEventListener('submit', (e) => { e.preventDefault(); grid.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    limpiar.addEventListener('click', () => { buscar.value = ''; aplicar(); buscar.focus(); });
    orden.addEventListener('change', aplicar);
    areas.forEach((b) => b.addEventListener('click', () => {
        area = b.dataset.paemArea;
        const url = new URL(location.href);
        if (area) url.searchParams.set('area', area); else url.searchParams.delete('area');
        history.replaceState(null, '', url);
        aplicar();
    }));
    document.querySelector('[data-paem-todos]').addEventListener('click', () => { buscar.value = ''; area = ''; aplicar(); });
    const q = new URLSearchParams(location.search).get('q');
    if (q) buscar.value = q;
    aplicar();
}

// ---------- Ventana de bienvenida de los programas (portada: al entrar o al recargar) ----------
export function initPromoProgramas() {
    const promo = document.querySelector('[data-promo]');
    if (!promo) return;
    // Sale al recargar la página (botón del navegador o F5) o al entrar directo / desde otra web.
    // No sale al navegar dentro de la web (buscar, filtrar, comprar, volver atrás).
    const nav = performance.getEntriesByType?.('navigation')?.[0];
    const tipo = nav?.type || (performance.navigation?.type === 1 ? 'reload' : 'navigate');
    let desdeLaWeb = false;
    try { desdeLaWeb = !!document.referrer && new URL(document.referrer).origin === location.origin; } catch { /* */ }
    const mostrar = tipo === 'reload' || (tipo === 'navigate' && !desdeLaWeb);
    if (!mostrar) return;

    let anterior = null;
    const cerrar = () => {
        if (promo.hidden) return;
        promo.classList.add('saliendo');
        setTimeout(() => {
            promo.hidden = true;
            promo.classList.remove('saliendo');
            document.body.classList.remove('im-sin-scroll');
            window.__imPromoAbierta = false;
            document.dispatchEvent(new CustomEvent('im-promo-cerrada'));
            anterior?.focus?.({ preventScroll: true });
        }, 220);
    };
    const abrir = () => {
        anterior = document.activeElement;
        promo.hidden = false;
        document.body.classList.add('im-sin-scroll');
        promo.querySelector('.im-promo-btn')?.focus({ preventScroll: true });
    };
    promo.querySelectorAll('[data-promo-cerrar]').forEach((b) => b.addEventListener('click', cerrar));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });

    // Avisa al asistente para que espere a que se cierre esta ventana
    window.__imPromoAbierta = true;
    setTimeout(abrir, 1200);
}
