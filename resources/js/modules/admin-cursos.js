// Panel › Cursos: vista previa del video / imagen y precio de oferta al editar.
export function initAdminCursos() {
    const form = document.querySelector('[data-form-curso]');
    if (!form) return;

    const video = form.querySelector('[name="video_url"]');
    const vista = form.querySelector('[data-vista-video]');
    const idDe = (url) => {
        const m = String(url || '').match(/(?:youtu\.be\/|v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{11})/) || String(url || '').match(/^([A-Za-z0-9_-]{11})$/);
        return m ? m[1] : null;
    };
    const verVideo = () => {
        if (!vista) return;
        const id = idDe(video.value.trim());
        vista.innerHTML = id
            ? `<img src="https://i.ytimg.com/vi/${id}/hqdefault.jpg" alt="Miniatura del video" class="img-fluid rounded"><small class="text-success d-block mt-1"><i class="fa-solid fa-circle-check"></i> Video reconocido (${id})</small>`
            : (video.value.trim() ? '<small class="text-danger"><i class="fa-solid fa-circle-xmark"></i> No es un enlace de YouTube válido</small>' : '');
    };
    video?.addEventListener('input', verVideo);
    verVideo();

    const imagen = form.querySelector('[name="imagen_archivo"]');
    imagen?.addEventListener('change', () => {
        const f = imagen.files[0];
        const img = form.querySelector('[data-vista-imagen]');
        if (f && img) { img.src = URL.createObjectURL(f); img.hidden = false; }
    });

    // Descuento calculado al escribir el precio de oferta
    const precio = form.querySelector('[name="precio"]');
    const oferta = form.querySelector('[name="precio_oferta"]');
    const desc = form.querySelector('[data-descuento]');
    const calc = () => {
        const p = Number(precio.value); const o = Number(oferta.value);
        desc.textContent = p > 0 && o > 0 && o < p ? `${Math.round(100 - (o * 100) / p)} % de descuento` : '';
    };
    precio?.addEventListener('input', calc);
    oferta?.addEventListener('input', calc);
    if (desc) calc();
}
