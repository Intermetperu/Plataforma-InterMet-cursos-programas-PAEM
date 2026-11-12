// Convierte un número a formato de soles: 1234.5 -> "S/ 1,234.50"
export function soles(n) {
    const value = Math.abs(Number(n) || 0);
    const [ent, dec] = value.toFixed(2).split('.');
    return `S/ ${ent.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}.${dec}`;
}

// Lee un número escrito por el usuario (acepta coma o punto decimal)
export function numero(text) {
    const v = parseFloat(String(text ?? '').replace(',', '.'));
    return Number.isNaN(v) ? 0 : v;
}

export function $(selector, root = document) {
    return root.querySelector(selector);
}

export function $$(selector, root = document) {
    return Array.from(root.querySelectorAll(selector));
}

// Aviso pequeño arriba a la derecha (SweetAlert2, como la plantilla)
export function aviso(texto, tipo = 'success') {
    window.alertaSW(texto, tipo);
}
