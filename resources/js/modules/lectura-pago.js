// Analiza el texto que el OCR leyó de una captura de pago (Yape, Plin o banco)
// y lo compara con lo que el cliente declaró. Solo da pistas a recepción:
// una captura se puede editar, así que la confirmación final es ver el dinero en la app.

const MESES = {
    ene: 1, enero: 1, feb: 2, febrero: 2, mar: 3, marzo: 3, abr: 4, abril: 4, may: 5, mayo: 5, jun: 6, junio: 6,
    jul: 7, julio: 7, ago: 8, agosto: 8, set: 9, sep: 9, sept: 9, setiembre: 9, septiembre: 9, oct: 10, octubre: 10,
    nov: 11, noviembre: 11, dic: 12, diciembre: 12,
};

export const APPS = [
    ['Niubiz', /niubiz|pago ?link|pagoseguro|visanet/],
    ['Yape', /\byape|yapeaste|yapeo\b/],
    ['Plin', /\bplin\b|plineaste/],
    ['BCP', /\bbcp\b|banco de credito|credito del peru|viabcp/],
    ['Interbank', /interbank/],
    ['BBVA', /\bbbva\b|continental/],
    ['Scotiabank', /scotiabank/],
    ['Banco de la Nación', /banco de la nacion/],
    ['BanBif', /banbif/],
    ['Caja municipal', /\bcaja (arequipa|huancayo|piura|cusco|trujillo|sullana|ica|tacna|maynas)\b/],
];

const quitarTildes = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '');
export const normalizar = (t) => quitarTildes(String(t || '').toLowerCase()).replace(/[ \t]+/g, ' ');

/** «1,250.50» · «1.250,50» · «150» · «150,00» -> número */
function aNumero(txt) {
    let t = txt.replace(/\s/g, '');
    const coma = t.lastIndexOf(',');
    const punto = t.lastIndexOf('.');
    if (coma > -1 && punto > -1) {
        t = coma > punto ? t.replace(/\./g, '').replace(',', '.') : t.replace(/,/g, '');
    } else if (coma > -1) {
        t = /,\d{1,2}$/.test(t) ? t.replace(',', '.') : t.replace(/,/g, '');
    } else if (punto > -1 && !/\.\d{1,2}$/.test(t)) {
        t = t.replace(/\./g, '');
    }
    const n = Number(t);
    return Number.isFinite(n) ? n : null;
}

/** Montos que aparecen en la constancia (con «S/» delante, o con decimales) */
export function montos(texto) {
    const t = normalizar(texto);
    const encontrados = new Set();
    // «S/ 150.00», «S/150», «S/. 150»; el OCR a veces lee «S/» como «5/», «SI» o «S|»
    for (const m of t.matchAll(/(?:\bs|\b5|\$)\s?[\/|il1]\s?\.?\s*(\d{1,3}(?:[.,\s]\d{3})*(?:[.,]\d{1,2})?|\d+(?:[.,]\d{1,2})?)/g)) {
        const n = aNumero(m[1]);
        if (n !== null && n > 0) encontrados.add(n);
    }
    // «USD 72», «US$ 1,250.00», «$ 72»
    for (const m of t.matchAll(/(?:usd|us\s?\$|\$)\s?(\d{1,3}(?:[.,\s]\d{3})*(?:[.,]\d{1,2})?|\d+(?:[.,]\d{1,2})?)/g)) {
        const n = aNumero(m[1]);
        if (n !== null && n > 0) encontrados.add(n);
    }
    for (const m of t.matchAll(/\b(\d{1,3}(?:[.,]\d{3})*[.,]\d{2}|\d+[.,]\d{2})\b/g)) {
        const n = aNumero(m[1]);
        if (n !== null && n > 0) encontrados.add(n);
    }
    return [...encontrados];
}

/** Fechas que aparecen: 06/11/2026 · 6-11-26 · 06 nov. 2026 · 6 de noviembre de 2026 */
export function fechas(texto, anioBase = new Date().getFullYear()) {
    const t = normalizar(texto);
    const lista = [];
    const agregar = (d, m, a) => {
        let anio = Number(a || anioBase);
        if (anio < 100) anio += 2000;
        const dia = Number(d);
        if (m >= 1 && m <= 12 && dia >= 1 && dia <= 31 && anio > 2000 && anio < 2100) lista.push({ dia, mes: m, anio });
    };
    for (const m of t.matchAll(/\b(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})\b/g)) agregar(m[1], Number(m[2]), m[3]);
    for (const m of t.matchAll(/\b(\d{1,2})\s*(?:de\s+)?([a-z]{3,10})\.?\s*(?:de\s+|del\s+)?(\d{4})?\b/g)) {
        const mes = MESES[m[2]];
        if (mes) agregar(m[1], mes, m[3]);
    }
    return lista;
}

/** N.º de operación que muestra la captura (si lo encuentra junto a la palabra «operación») */
export function operacionEnCaptura(texto) {
    const t = normalizar(texto);
    const m = t.match(/(?:operacion|n(?:ro|um|°|º|o)?\.?\s*de\s*op\w*|cod(?:igo)?\.?\s*de\s*op\w*|nro\.?\s*op\w*)[^\d\n]{0,25}([\d\s-]{4,24}\d)/);
    return m ? m[1].replace(/[\s-]/g, '') : null;
}

const clave = (t) => normalizar(t).replace(/[^a-z0-9]/g, '');
// Letras que el OCR confunde con números dentro de un código
const comoDigitos = (t) => t.replace(/o/g, '0').replace(/[il]/g, '1').replace(/s/g, '5').replace(/b/g, '8').replace(/z/g, '2');

/**
 * Compara la lectura con lo declarado.
 * datos: { metodo, monto, adelanto, operacion, titulares: [..], fechaEnvio: 'YYYY-MM-DD', fechaReserva: 'YYYY-MM-DD' }
 * Devuelve { apps, checks: [{ clave, estado: 'ok'|'mal'|'nose', texto }], resumen }
 */
export function analizar(texto, datos, colores = {}) {
    const t = normalizar(texto);
    const checks = [];

    // 1) ¿De qué app es?
    const apps = APPS.filter(([, re]) => re.test(t)).map(([n]) => n);
    if (!apps.length && colores.yape) apps.push('Yape (por los colores)');
    if (!apps.length && colores.plin) apps.push('Plin (por los colores)');
    const esperado = { niubiz: 'Niubiz', yape: 'Yape', plin: 'Plin', bcp: 'BCP', interbank: 'Interbank', bbva: 'BBVA', scotiabank: 'Scotiabank' }[datos.metodo];
    if (!apps.length) {
        checks.push({ clave: 'app', estado: 'nose', texto: 'No dice «Niubiz» en la constancia (puede ser el correo o el voucher del banco): confirme en su panel de Niubiz.' });
    } else if (esperado && !apps.some((a) => a.startsWith(esperado))) {
        // Yape y Plin se pueden pagar entre sí: no es un error seguro
        checks.push({ clave: 'app', estado: 'nose', texto: `Parece de ${apps.join(', ')}; el cliente eligió ${esperado}. (Los pagos entre bancos y billeteras se cruzan: revise en su cuenta.)` });
    } else {
        checks.push({ clave: 'app', estado: 'ok', texto: `Constancia de ${apps.join(', ')}.` });
    }

    // 2) Monto
    const lista = montos(texto);
    const igual = (a, b) => Math.abs(a - b) < 0.011;
    if (!lista.length) {
        checks.push({ clave: 'monto', estado: 'nose', texto: 'No se pudo leer el monto en la constancia.' });
    } else if (lista.some((n) => igual(n, datos.monto))) {
        checks.push({ clave: 'monto', estado: 'ok', texto: `El monto ${datos.simbolo || 'S/'} ${datos.monto.toFixed(2)} aparece en la constancia.` });
    } else {
        const visto = lista.slice().sort((a, b) => b - a).slice(0, 3).map((n) => n.toFixed(2)).join(', ');
        checks.push({ clave: 'monto', estado: 'mal', texto: `El cliente dice ${datos.simbolo || 'S/'} ${datos.monto.toFixed(2)}, pero en la constancia se lee ${visto}.` });
    }

    // 3) N.º de operación
    const op = clave(datos.operacion);
    const enCaptura = operacionEnCaptura(texto);
    const todo = clave(texto);
    if (op && (todo.includes(op) || comoDigitos(todo).includes(comoDigitos(op)))) {
        checks.push({ clave: 'operacion', estado: 'ok', texto: `El n.º de operación ${datos.operacion} aparece en la constancia.` });
    } else if (enCaptura) {
        checks.push({ clave: 'operacion', estado: 'mal', texto: `El cliente escribió ${datos.operacion}, pero la captura dice ${enCaptura}.` });
    } else {
        checks.push({ clave: 'operacion', estado: 'nose', texto: `No se encontró el n.º de operación ${datos.operacion} en la constancia.` });
    }

    // 4) A quién se pagó (tu nombre como titular)
    const palabras = [...new Set((datos.titulares || []).flatMap((n) => normalizar(n).split(/[^a-z0-9]+/)))]
        .filter((p) => p.length >= 4 && !['sociedad', 'anonima', 'cerrada', 'empresa', 'hotel', 'hostal', 'hospedaje', 'eirl', 'titular'].includes(p));
    if (!palabras.length) {
        checks.push({ clave: 'titular', estado: 'nose', texto: 'Ponga el nombre del comercio en Configuración › Pagos para comprobar a quién se pagó.' });
    } else {
        const vistas = palabras.filter((p) => t.includes(p));
        if (vistas.length) checks.push({ clave: 'titular', estado: 'ok', texto: `Aparece su comercio como destinatario (${vistas.join(' ')}).` });
        else checks.push({ clave: 'titular', estado: 'mal', texto: 'No aparece su comercio como destinatario: el pago pudo ir a otra persona.' });
    }

    // 5) Fecha
    const vistas = fechas(texto, Number((datos.fechaEnvio || '').slice(0, 4)) || undefined);
    if (!vistas.length) {
        checks.push({ clave: 'fecha', estado: 'nose', texto: 'No se encontró la fecha en la constancia.' });
    } else {
        const iso = (f) => `${f.anio}-${String(f.mes).padStart(2, '0')}-${String(f.dia).padStart(2, '0')}`;
        const desde = datos.fechaReserva || datos.fechaEnvio;
        const hasta = datos.fechaEnvio || datos.fechaReserva;
        const dentro = vistas.find((f) => iso(f) >= desde && iso(f) <= hasta);
        const ver = (f) => `${String(f.dia).padStart(2, '0')}/${String(f.mes).padStart(2, '0')}/${f.anio}`;
        if (dentro) checks.push({ clave: 'fecha', estado: 'ok', texto: `Fecha del pago: ${ver(dentro)} (coincide con la fecha declarada).` });
        else checks.push({ clave: 'fecha', estado: 'mal', texto: `La constancia dice ${ver(vistas[0])}, pero declaró el ${desde.split('-').reverse().join('/')}: puede ser una constancia vieja.` });
    }

    const malos = checks.filter((c) => c.estado === 'mal').length;
    const buenos = checks.filter((c) => c.estado === 'ok').length;
    const resumen = malos ? 'mal' : (buenos >= 3 ? 'ok' : 'nose');
    return { apps, checks, resumen };
}
