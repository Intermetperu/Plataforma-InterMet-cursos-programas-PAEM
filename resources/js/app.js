// Scripts propios. Bootstrap, jQuery y los iconos los carga la plantilla (public/assets).
import Swal from 'sweetalert2';

import { initCatalogo } from './modules/catalogo';
import { initRegistroPago } from './modules/registro-pago';
import { initOcrPago } from './modules/ocr-pago';
import { initAdminCursos } from './modules/admin-cursos';
import { initChatbot } from './modules/chatbot';
import { initListaProgramas, initProgramas, initPromoProgramas } from './modules/programas';

window.Swal = Swal;
window.alertaSW = function (mensaje, tipo = 'success') {
    Swal.fire({ position: 'top-end', icon: tipo, title: mensaje, showConfirmButton: false, timer: 2500, toast: true });
};

function iniciar() {
    // Cada módulo solo se activa si su pantalla está abierta
    initCatalogo();
    initRegistroPago();
    initOcrPago();
    initAdminCursos();
    initChatbot();
    initProgramas();
    initListaProgramas();
    initPromoProgramas();

    // Formularios que piden confirmación: <form data-confirmar="¿Seguro?">
    document.querySelectorAll('form[data-confirmar]').forEach((form) => form.addEventListener('submit', (e) => {
        if (form.dataset.confirmado) return;
        e.preventDefault();
        Swal.fire({
            text: form.dataset.confirmar, icon: 'question', showCancelButton: true,
            confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar', confirmButtonColor: '#0b5ed7', reverseButtons: true,
        }).then((r) => {
            if (r.isConfirmed) { form.dataset.confirmado = '1'; form.requestSubmit ? form.requestSubmit() : form.submit(); }
        });
    }));

    // Evita el doble clic al enviar
    document.querySelectorAll('form[method="POST"]').forEach((form) => form.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = true; });
    }));

    // Copiar al portapapeles: <button data-copiar="texto">
    document.querySelectorAll('[data-copiar]').forEach((b) => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.copiar); window.alertaSW('Copiado'); } catch { window.prompt('Copie el texto:', b.dataset.copiar); }
    }));

    // Programas de alta especialización sin enlace: ventana «Próximamente»
    const pronto = document.getElementById('programa-pronto');
    if (pronto) {
        let origen = null;
        const cerrar = () => { pronto.hidden = true; document.body.classList.remove('im-sin-scroll'); origen?.focus(); };
        document.querySelectorAll('[data-pronto]').forEach((a) => a.addEventListener('click', (e) => {
            e.preventDefault();
            origen = a;
            pronto.querySelector('[data-pronto-nombre]').textContent = a.dataset.nombre.trim();
            pronto.querySelector('[data-pronto-icono]').className = 'bx ' + a.dataset.icono;
            pronto.querySelector('[data-pronto-cab]').className = 'im-pronto-cab im-prog-' + a.dataset.color;
            pronto.querySelector('[data-pronto-wa]').href = a.dataset.wa;
            pronto.hidden = false;
            document.body.classList.add('im-sin-scroll');
            pronto.querySelector('[data-pronto-wa]').focus();
        }));
        pronto.querySelectorAll('[data-cerrar-pronto]').forEach((b) => b.addEventListener('click', cerrar));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !pronto.hidden) cerrar(); });
    }

    // Ver / ocultar contraseña
    document.querySelectorAll('[data-ver-clave]').forEach((b) => b.addEventListener('click', () => {
        const input = b.parentElement.querySelector('input');
        const ver = input.type === 'password';
        input.type = ver ? 'text' : 'password';
        b.querySelector('i').className = ver ? 'bx bx-show' : 'bx bx-hide';
    }));

    // Aviso de bienvenida / sesión cerrada: se oculta solo
    const aviso = document.querySelector('[data-aviso]');
    if (aviso) {
        const ocultar = () => aviso.classList.add('oculto');
        aviso.querySelector('[data-cerrar-aviso]')?.addEventListener('click', ocultar);
        setTimeout(ocultar, 6000);
    }

    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new window.bootstrap.Tooltip(el));
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
else iniciar();
