// Asistente del panel lateral: conversa con /asistente y recuerda la charla mientras el visitante navega.
// Al cargar la web aparece solo (una vez por visita): en la PC se abre el panel con su animación de entrada
// y en el celular sale una tarjeta de saludo junto al botón. Se configura en Admin › Configuración › Asistente.
const CLAVE = 'im-asistente-v1';
const ESCRITORIO = '(min-width: 992px)';

export function initChatbot() {
    const raiz = document.querySelector('[data-bot]');
    if (!raiz) return;

    const panel = raiz.querySelector('.im-bot-panel');
    const lista = raiz.querySelector('[data-bot-mensajes]');
    const form = raiz.querySelector('[data-bot-form]');
    const input = raiz.querySelector('[data-bot-texto]');
    const lanzador = raiz.querySelector('.im-bot-lanzador');
    const avance = raiz.querySelector('[data-bot-avance]');
    const avanceTexto = raiz.querySelector('[data-bot-avance-texto]');
    const avanceChips = raiz.querySelector('[data-bot-avance-chips]');
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const esEscritorio = () => window.matchMedia(ESCRITORIO).matches;

    let estado = leer() || {};
    estado = { abierto: false, mensajes: [], autoVisto: false, abrioAlgunaVez: false, ...estado };
    let enviando = false;
    let interactuo = false;

    function leer() {
        try { return JSON.parse(sessionStorage.getItem(CLAVE)); } catch { return null; }
    }
    function guardar() {
        try {
            estado.mensajes = estado.mensajes.slice(-40);
            sessionStorage.setItem(CLAVE, JSON.stringify(estado));
        } catch { /* sin almacenamiento: la charla dura solo en esta página */ }
    }

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const formato = (t) => esc(t).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    const urlSegura = (u) => (/^(https?:|mailto:|\/)/i.test(String(u || '')) ? u : '#');
    const espera = (ms) => new Promise((r) => setTimeout(r, ms));

    // ---------- Efecto «escribiendo letra por letra» (respeta negritas y saltos de línea) ----------
    function teclear(el, { ritmo = 1 } = {}) {
        if (sinMovimiento) return Promise.resolve();
        const nodos = [];
        const recorrer = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        while (recorrer.nextNode()) nodos.push(recorrer.currentNode);
        const textos = nodos.map((n) => n.textContent);
        const total = textos.reduce((a, t) => a + t.length, 0);
        nodos.forEach((n) => { n.textContent = ''; });
        // Rápido pero visible: un mensaje corto tarda ~1 s y uno largo no más de ~1.6 s
        const porCuadro = Math.max(1, Math.ceil(total / (90 * ritmo)));
        el.classList.add('tecleando');

        return new Promise((listo) => {
            let i = 0;
            let escritos = 0;
            const paso = () => {
                let quedan = porCuadro;
                while (quedan > 0 && i < nodos.length) {
                    const falta = textos[i].length - nodos[i].textContent.length;
                    const toma = Math.min(falta, quedan);
                    nodos[i].textContent = textos[i].slice(0, nodos[i].textContent.length + toma);
                    quedan -= toma;
                    escritos += toma;
                    if (nodos[i].textContent.length >= textos[i].length) i++;
                }
                if (el.closest('.im-bot-mensajes')) alFinal();
                if (escritos < total) requestAnimationFrame(paso);
                else { el.classList.remove('tecleando'); listo(); }
            };
            requestAnimationFrame(paso);
        });
    }

    // Botones y tarjetas entran uno tras otro
    function revelar(cont) {
        cont.querySelectorAll('.im-bot-chip, .im-bot-curso, .im-bot-vermas').forEach((el, n) => el.style.setProperty('--i', n));
        cont.classList.remove('esperando');
        cont.classList.add('revela');
    }

    // ---------- Pintar mensajes ----------
    function tarjeta(c) {
        // Portada del tema de fondo: se ve si el curso no tiene imagen o si la imagen no carga
        const media = `<span class="im-bot-curso-img im-t-${esc(c.color)}"><i class="bx ${esc(c.icono)}"></i>${c.imagen ? `<img src="${esc(urlSegura(c.imagen))}" alt="" loading="lazy">` : ''}</span>`;
        return `<a class="im-bot-curso" href="${esc(urlSegura(c.url))}">
            ${media}
            <span class="im-bot-curso-txt">
                <small>${c.numero ? `<b>${esc(c.numero)}</b> · ` : ''}${esc(c.meta)}</small>
                <span class="im-bot-curso-titulo">${esc(c.titulo)}</span>
                <span class="im-bot-curso-precio">${esc(c.precio)}${c.antes ? ` <s>${esc(c.antes)}</s>` : ''}</span>
            </span>
            <i class="bx bx-chevron-right"></i>
        </a>`;
    }

    function boton(b) {
        const icono = b.icono ? `<i class="bx ${esc(b.icono)}"></i>` : '';
        if (b.tipo === 'pregunta') {
            return `<button type="button" class="im-bot-chip" data-enviar="${esc(b.enviar)}">${icono}${esc(b.texto)}</button>`;
        }
        const nueva = b.nueva ? ' target="_blank" rel="noopener"' : '';
        return `<a class="im-bot-chip ${b.tipo === 'whatsapp' ? 'wa' : 'lleno'}" href="${esc(urlSegura(b.url))}"${nueva}>${icono}${esc(b.texto)}</a>`;
    }

    /** animar: entra con movimiento · teclear: el texto se escribe solo y luego aparecen los botones */
    async function pintar(m, { animar = true, conTeclado = false, ritmo = 1 } = {}) {
        const div = document.createElement('div');
        div.className = `im-bot-msg ${m.rol === 'yo' ? 'yo' : 'bot'}${animar ? ' nuevo' : ''}`;
        if (m.rol === 'yo') {
            div.innerHTML = `<div class="im-bot-globo">${esc(m.texto)}</div>`;
            lista.appendChild(div);
            return;
        }
        let html = `<div class="im-bot-globo">${formato(m.texto)}</div>`;
        if (m.cursos?.length) html += `<div class="im-bot-cursos">${m.cursos.map(tarjeta).join('')}</div>`;
        if (m.ver_mas) html += `<a class="im-bot-vermas" href="${esc(urlSegura(m.ver_mas.url))}">${esc(m.ver_mas.texto)} <i class="bx bx-right-arrow-alt"></i></a>`;
        if (m.botones?.length) html += `<div class="im-bot-chips">${m.botones.map(boton).join('')}</div>`;
        div.innerHTML = html;
        if (conTeclado && !sinMovimiento) div.classList.add('esperando');
        lista.appendChild(div);
        if (conTeclado) {
            await teclear(div.querySelector('.im-bot-globo'), { ritmo });
            revelar(div);
            alFinal();
        }
    }

    const alFinal = () => { lista.scrollTop = lista.scrollHeight; };

    async function agregar(m, opciones = {}) {
        estado.mensajes.push(m);
        guardar();
        const p = pintar(m, opciones);
        alFinal();
        await p;
    }

    function escribiendo(si) {
        lista.querySelector('.im-bot-escribiendo')?.remove();
        if (si) {
            lista.insertAdjacentHTML('beforeend', '<div class="im-bot-msg bot im-bot-escribiendo" aria-label="Escribiendo"><div class="im-bot-globo"><span></span><span></span><span></span></div></div>');
            alFinal();
        }
    }

    // ---------- Conversar ----------
    let pidiendoBienvenida = null;
    function traerBienvenida() {
        pidiendoBienvenida ??= fetch(raiz.dataset.bienvenida, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .catch(() => ({ texto: '¡Hola! 👋 ¿En qué te ayudo?', botones: [] }));
        return pidiendoBienvenida;
    }

    async function bienvenida({ conEfecto = false } = {}) {
        escribiendo(true);
        const [d] = await Promise.all([traerBienvenida(), espera(conEfecto ? 1100 : 400)]);
        escribiendo(false);
        pidiendoBienvenida = null;
        await agregar({ rol: 'bot', ...d }, { conTeclado: true, ritmo: conEfecto ? 0.8 : 1 });
    }

    async function enviar(texto) {
        texto = String(texto || '').trim().slice(0, 500);
        if (!texto || enviando) return;
        enviando = true;
        interactuo = true;
        form.classList.add('ocupado');

        const historial = estado.mensajes.slice(-6).map((m) => ({ rol: m.rol, texto: String(m.texto || '').slice(0, 1500) }));
        const ultimoBot = [...estado.mensajes].reverse().find((m) => m.rol === 'bot' && m.cursos?.length);
        agregar({ rol: 'yo', texto });
        escribiendo(true);
        const inicio = Date.now();

        try {
            const r = await fetch(raiz.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({
                    mensaje: texto,
                    curso: raiz.dataset.curso || null,
                    ultimos: (ultimoBot?.cursos || []).map((c) => c.id).slice(0, 3),
                    historial,
                }),
            });
            let d;
            if (r.status === 429) d = { texto: 'Recibí muchas preguntas seguidas. Espera un minuto y vuelve a escribirme, por favor.' };
            else if (r.status === 419) d = { texto: 'La página estuvo abierta mucho tiempo. **Recárgala** y vuelve a escribirme, por favor.' };
            else if (!r.ok) throw new Error(r.status);
            else d = await r.json();

            await espera(Math.max(0, 600 - (Date.now() - inicio)));
            escribiendo(false);
            await agregar({ rol: 'bot', ...d }, { conTeclado: true });
        } catch {
            escribiendo(false);
            await agregar({
                rol: 'bot',
                texto: 'No pude conectarme en este momento. Revisa tu internet o escríbenos por WhatsApp.',
                botones: [{ tipo: 'whatsapp', texto: 'Escribir por WhatsApp', url: raiz.dataset.wa, icono: 'bxl-whatsapp', nueva: true }],
            });
        } finally {
            enviando = false;
            form.classList.remove('ocupado');
        }
    }

    // ---------- Abrir / cerrar el panel ----------
    function abrir({ automatico = false, sinEfecto = false } = {}) {
        ocultarAvance();
        if (!automatico) interactuo = true;
        if (automatico && !sinMovimiento) {
            raiz.classList.add('efecto');
            setTimeout(() => raiz.classList.remove('efecto'), 2200);
        }
        panel.hidden = false;
        if (sinEfecto) raiz.classList.add('abierto');
        else requestAnimationFrame(() => raiz.classList.add('abierto'));
        raiz.classList.remove('llamando');
        lanzador.setAttribute('aria-expanded', 'true');
        if (!esEscritorio()) document.body.classList.add('im-sin-scroll');
        estado.abierto = true;
        estado.abrioAlgunaVez = true;
        guardar();
        if (!estado.mensajes.length) bienvenida({ conEfecto: automatico });
        alFinal();
        // Si se abrió solo, no le quitamos el foco a la página (ni sube el teclado en el celular)
        if (!automatico && window.matchMedia('(hover: hover)').matches) setTimeout(() => input.focus(), 260);
    }

    function cerrar() {
        interactuo = true;
        raiz.classList.remove('abierto', 'efecto');
        lanzador.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('im-sin-scroll');
        estado.abierto = false;
        guardar();
        setTimeout(() => { if (!raiz.classList.contains('abierto')) panel.hidden = true; }, 300);
        lanzador.focus({ preventScroll: true });
    }

    // ---------- Tarjeta de saludo junto al botón (celular, o si así se configuró) ----------
    async function mostrarAvance() {
        avanceTexto.innerHTML = '<span class="im-bot-puntos"><span></span><span></span><span></span></span>';
        avanceChips.innerHTML = '';
        avance.hidden = false;
        const [d] = await Promise.all([traerBienvenida(), espera(1200)]);
        if (avance.hidden) return;

        // La misma bienvenida queda guardada para cuando abra el chat
        if (!estado.mensajes.length) {
            pidiendoBienvenida = null;
            estado.mensajes.push({ rol: 'bot', ...d });
            guardar();
            pintar(estado.mensajes[0], { animar: false });
        }
        const corto = String(d.texto || '').split('\n\n')[0];
        avanceTexto.innerHTML = formato(corto);
        avanceChips.innerHTML = (d.botones || []).filter((b) => b.tipo === 'pregunta').slice(0, 3).map(boton).join('');
        avance.classList.add('esperando');
        await teclear(avanceTexto, { ritmo: 0.8 });
        revelar(avance);
    }

    function ocultarAvance() {
        if (avance.hidden) return;
        avance.classList.add('saliendo');
        setTimeout(() => { avance.hidden = true; avance.classList.remove('saliendo', 'revela'); }, 250);
    }

    // ---------- Eventos ----------
    raiz.querySelectorAll('[data-bot-abrir]').forEach((b) => b.addEventListener('click', () => abrir()));
    raiz.querySelectorAll('[data-bot-cerrar]').forEach((b) => b.addEventListener('click', cerrar));
    raiz.querySelector('[data-bot-avance-cerrar]').addEventListener('click', () => { interactuo = true; ocultarAvance(); });
    avanceChips.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-enviar]');
        if (!chip) return;
        abrir();
        enviar(chip.dataset.enviar);
    });
    raiz.querySelector('[data-bot-reiniciar]').addEventListener('click', () => {
        estado.mensajes = [];
        guardar();
        lista.innerHTML = '';
        bienvenida();
        input.focus();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && raiz.classList.contains('abierto')) cerrar(); });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const t = input.value;
        input.value = '';
        enviar(t);
    });
    lista.addEventListener('error', (e) => { if (e.target.tagName === 'IMG') e.target.remove(); }, true);
    lista.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-enviar]');
        if (chip) enviar(chip.dataset.enviar);
    });

    // ---------- Al cargar la página ----------
    estado.mensajes.forEach((m) => pintar(m, { animar: false }));
    if (!estado.abrioAlgunaVez) raiz.classList.add('llamando');

    if (estado.abierto && esEscritorio()) {
        abrir({ sinEfecto: true });
    } else if (!estado.autoVisto && raiz.dataset.auto !== 'no') {
        const segundos = Math.min(30, Math.max(0, Number(raiz.dataset.autoSeg) || 2));
        const aparecer = () => {
            if (interactuo || raiz.classList.contains('abierto')) return;
            // Si está abierta la ventana de programas, el asistente espera a que la cierren
            if (window.__imPromoAbierta) {
                document.addEventListener('im-promo-cerrada', () => setTimeout(aparecer, 1500), { once: true });
                return;
            }
            estado.autoVisto = true;
            guardar();
            if (raiz.dataset.auto === 'panel' && esEscritorio()) abrir({ automatico: true });
            else mostrarAvance();
        };
        setTimeout(aparecer, segundos * 1000);
    }
}
