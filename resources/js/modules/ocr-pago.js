import { analizar } from './lectura-pago';

// Recepción: lee la captura del pago con OCR (Tesseract.js, alojado en public/vendor/tesseract)
// y muestra en verde / rojo si coincide con lo que el cliente declaró. Corre en la computadora
// de recepción: no usa internet ni servicios de pago.

let trabajador = null; // un solo lector para toda la pantalla
let avance = null; // a qué caja se le muestra el avance
let cola = Promise.resolve();
const hechos = new Map();

async function lector(base) {
    if (!trabajador) {
        trabajador = import('tesseract.js').then(({ createWorker }) => createWorker('spa', 1, {
            workerPath: `${base}/worker.min.js`,
            corePath: `${base}/core`,
            langPath: `${base}/lang`,
            logger: (m) => avance?.(m),
        }));
        trabajador.catch(() => { trabajador = null; });
    }
    return trabajador;
}

/** Imagen -> lienzo de ~1000 px de ancho para el OCR + colores de Yape / Plin */
async function preparar(url) {
    const img = new Image();
    img.src = url;
    await img.decode();
    const lienzo = (ancho) => {
        const c = document.createElement('canvas');
        c.width = Math.round(ancho);
        c.height = Math.round(ancho * img.naturalHeight / img.naturalWidth);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        return c;
    };
    const normal = lienzo(img.naturalWidth >= 700 && img.naturalWidth <= 1400 ? img.naturalWidth : 1000);

    // Colores: el morado de Yape y el turquesa de Plin
    const chico = document.createElement('canvas');
    chico.width = 80;
    chico.height = Math.max(1, Math.round(80 * img.naturalHeight / img.naturalWidth));
    const c2 = chico.getContext('2d', { willReadFrequently: true });
    c2.drawImage(img, 0, 0, chico.width, chico.height);
    const px = c2.getImageData(0, 0, chico.width, chico.height).data;
    let morado = 0;
    let turquesa = 0;
    for (let i = 0; i < px.length; i += 4) {
        const [r, g, b] = [px[i], px[i + 1], px[i + 2]];
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        if (max - min < 50) continue; // grises
        if (b > g + 40 && r > g + 30 && r < 200) morado++;
        else if (g > r + 60 && b > r + 40 && Math.abs(g - b) < 70) turquesa++;
    }
    const total = px.length / 4;
    return { lienzo: normal, colores: { yape: morado / total > 0.06, plin: turquesa / total > 0.06 } };
}

const ICONOS = {
    ok: '<i class="fa-solid fa-circle-check text-success"></i>',
    mal: '<i class="fa-solid fa-circle-xmark text-danger"></i>',
    nose: '<i class="fa-solid fa-circle-question text-secondary"></i>',
};
const RESUMEN = {
    ok: ['bg-light-success', 'text-success', 'fa-circle-check', 'Lo que se pudo leer coincide.', 'Igual confirme en su cuenta que el dinero llegó.'],
    mal: ['bg-light-danger', 'text-danger', 'fa-triangle-exclamation', 'Hay datos que NO coinciden.', 'No envíe el acceso sin ver el pago en su cuenta. Si no llegó, rechace el pedido.'],
    nose: ['bg-light-warning', 'text-warning-emphasis', 'fa-circle-question', 'No se pudo leer todo.', 'Revise la constancia a mano y confirme en su cuenta.'],
};

function pintar(caja, r, texto) {
    const [fondo, color, icono, titulo, consejo] = RESUMEN[r.resumen];
    caja.innerHTML = `
        <div class="ocr-resumen ${fondo}">
            <i class="fa-solid ${icono} ${color}"></i>
            <div><b class="${color}">${titulo}</b><span>${consejo}</span></div>
        </div>
        <ul class="ocr-lista">${r.checks.map((c) => `<li>${ICONOS[c.estado]}<span></span></li>`).join('')}</ul>
        <details class="ocr-texto"><summary>Ver el texto que se leyó</summary><pre></pre></details>`;
    caja.querySelectorAll('.ocr-lista li span').forEach((s, i) => { s.textContent = r.checks[i].texto; });
    caja.querySelector('pre').textContent = texto.trim() || '(no se leyó texto)';
}

async function leer(caja) {
    const d = caja.dataset;
    if (hechos.has(d.ocr)) { pintar(caja, ...hechos.get(d.ocr)); return; }
    const barra = caja.querySelector('[data-ocr-avance]');
    const estado = caja.querySelector('[data-ocr-estado]');
    try {
        const { lienzo, colores } = await preparar(d.src);
        avance = (m) => {
            if (!barra) return;
            const textos = { 'loading tesseract core': 'Preparando el lector…', 'loading language traineddata': 'Cargando español…', 'recognizing text': 'Leyendo la captura…' };
            if (textos[m.status] && estado) estado.textContent = textos[m.status];
            if (m.status === 'recognizing text') barra.style.width = `${Math.round(m.progress * 100)}%`;
        };
        const w = await lector(d.base || '/vendor/tesseract');
        // Dos pasadas: la normal (párrafos) y la de «texto disperso», que sí lee el monto en letras grandes
        let texto = '';
        for (const modo of ['3', '11']) {
            await w.setParameters({ tessedit_pageseg_mode: modo });
            texto += (await w.recognize(lienzo)).data.text + '\n';
        }
        const data = { text: texto };
        const resultado = analizar(data.text, {
            metodo: d.metodo,
            simbolo: d.simbolo,
            monto: Number(d.monto),
            adelanto: Number(d.adelanto),
            operacion: d.operacion,
            titulares: JSON.parse(d.titulares || '[]'),
            fechaEnvio: d.fechaEnvio,
            fechaReserva: d.fechaReserva,
        }, colores);
        hechos.set(d.ocr, [resultado, data.text]);
        pintar(caja, resultado, data.text);
    } catch (e) {
        console.error(e);
        caja.innerHTML = '<p class="mb-0 text-secondary font-13"><i class="fa-solid fa-circle-info me-1"></i>No se pudo leer la constancia automáticamente. Ábrala y revísela a mano.</p>';
    }
}

export function initOcrPago() {
    const cajas = document.querySelectorAll('[data-ocr]');
    if (!cajas.length) return;
    // Lee cada captura recién cuando su detalle se muestra en pantalla
    const ver = new IntersectionObserver((entradas) => entradas.forEach((e) => {
        if (!e.isIntersecting || e.target.dataset.leido) return;
        e.target.dataset.leido = '1';
        cola = cola.then(() => leer(e.target));
    }), { rootMargin: '0px 0px 3000px 0px' }); // apenas se muestra el detalle, aunque esté más abajo
    cajas.forEach((c) => ver.observe(c));
}
