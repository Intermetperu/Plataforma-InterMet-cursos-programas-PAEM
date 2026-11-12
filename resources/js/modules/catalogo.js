// Web pública: filtros que se aplican solos, videos de YouTube en ventana y video del detalle.
export function initCatalogo() {
    // Filtros: al cambiar idioma u orden se aplica sin apretar botón
    document.querySelectorAll('[data-filtros] select[data-auto]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

    // Videos de las tarjetas: se abren en una ventana (plantilla: Magnific Popup)
    const $ = window.jQuery;
    if ($ && $.fn.magnificPopup) {
        $('.im-video-pop').magnificPopup({ type: 'iframe', mainClass: 'mfp-fade', removalDelay: 160, preloader: false, fixedContentPos: false });
    }

    // Detalle: el video se carga recién al darle play
    document.querySelectorAll('[data-youtube]').forEach((caja) => caja.addEventListener('click', () => {
        const id = caja.dataset.youtube;
        caja.innerHTML = `<iframe src="https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0" title="Video de presentación" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    }, { once: true }));

    // «Comprar»: el link de pago se abre en otra pestaña y esta lleva al curso, al paso «Registrar mi pago»
    document.querySelectorAll('[data-comprar]').forEach((a) => a.addEventListener('click', (e) => {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        setTimeout(() => { window.location.href = a.dataset.comprar; }, 150);
    }));

    // Barra de compra fija en el celular (detalle del curso)
    if (document.querySelector('.im-barra-compra')) document.body.classList.add('im-con-barra');
}
