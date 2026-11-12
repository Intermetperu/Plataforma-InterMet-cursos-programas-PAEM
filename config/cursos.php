<?php

/*
| Datos de la empresa y de la tienda de cursos.
| Todo se puede cambiar desde Admin › Configuración (se guarda en la tabla «ajustes»).
*/
return [
    'nombre' => 'InterMet Perú',
    'eslogan' => 'Cursos y congresos digitales',
    'descripcion' => 'Los cursos y congresos digitales ya han sido realizados por InterMet y ahora usted puede verlos en cualquier momento desde su computadora. Son creados para ingenieros, técnicos, participantes independientes y estudiantes. Todos los cursos son originales, hechos 100 % por InterMet con expertos de primer nivel de los 5 continentes.',

    // Contacto
    'correo_pedidos' => 'digital@intermetperu.com',
    'correo_informes' => 'informes@encuentrometalurgia.com',
    'whatsapp' => '51960995971',
    'whatsapp_2' => '51987975959',
    'telefono' => '(51) 987 975 959',
    'horario' => 'Lunes a viernes de 9:00 a 18:00',
    'web' => 'https://intermetperu.com',
    'facebook' => '',
    'linkedin' => '',
    'youtube' => '',

    // Pagos: cada curso tiene su link de pago (Niubiz). Nombre del comercio que sale en el voucher
    // (sirve para revisar las constancias automáticamente).
    'titular' => 'COMINSAC InterMet',
    'tipo_cambio' => '3.40',
    'cuentas' => "BCP Soles: 193-14168753-0-70\nBCP Dólares: 194-30815773-1-41\nInterbank Soles: 288-3144785339\nInterbank Dólares: 898-3194044888",
    'yape_numero' => '',
    'yape_qr' => '',
    'moneygram' => 'Desde fuera del Perú puede pagar por MoneyGram a nombre del titular.',

    // Lo que incluye cada curso
    'incluye' => "Grabación en formato MP4\nMaterial de trabajo\nCertificado digital de participación",
    'dirigido_a' => "Ingenieros metalúrgicos\nIngenieros químicos\nIngenieros de minas y geólogos\nIngenieros mecánicos y electrónicos\nGerentes y superintendentes\nInvestigadores y personal de laboratorios\nIngenieros técnico-comerciales\nCatedráticos y estudiantes avanzados\nIngenieros de otras especialidades con experiencia en minas y plantas",
    'envio' => 'Le enviaremos los enlaces de descarga a su correo electrónico (vía WeTransfer) apenas verifiquemos su pago.',
    'por_pagina' => 12,

    // Programas de alta especialización (portada). «enlace» vacío = botón de WhatsApp pidiendo información.
    // color: rojo | marron | naranja | azul · icono: https://boxicons.com
    'programas' => [
        ['nombre' => 'Flotación', 'apellido' => 'de Minerales', 'titulo' => 'Programa de alta especialización en flotación de minerales', 'color' => 'naranja', 'icono' => 'bx-water', 'enlace' => null],
        ['nombre' => 'Aguas', 'apellido' => 'y Relaves', 'titulo' => 'Programa de alta especialización en aguas y relaves', 'color' => 'azul', 'icono' => 'bx-droplet', 'enlace' => null],
        ['nombre' => 'Minería', 'apellido' => '5.0', 'titulo' => 'Programa de alta especialización en minería 5.0', 'color' => 'rojo', 'icono' => 'bx-chip', 'enlace' => null],
        ['nombre' => 'Excelencia', 'apellido' => 'Operacional', 'titulo' => 'Programa de alta especialización en excelencia operacional', 'color' => 'marron', 'icono' => 'bx-trending-up', 'enlace' => null],
    ],

    // Asistente (chat del panel lateral). chatbot_ia = '1' usa IA si hay ANTHROPIC_API_KEY en el .env
    'chatbot_activo' => '1',
    'chatbot_nombre' => 'Asistente InterMet',
    'chatbot_bienvenida' => '¡Hola! Soy el asistente de InterMet. Te ayudo a encontrar el curso que buscas, ver precios y resolver tus dudas sobre la compra.',
    'chatbot_ia' => '0',
    // Al cargar la web: 'panel' = se abre solo en la PC y saluda en el celular · 'saludo' = solo el saludo · 'no'
    'chatbot_auto' => 'panel',
    'chatbot_auto_seg' => '2',

    // Ventana «Programas de alta especialización» al entrar a la portada o recargarla: '1' sí, '0' no
    'popup_programas' => '1',

    // Hoja de Google Sheets con la lista de cursos y links de pago (Cursos › Importar)
    'hoja_cursos' => 'https://docs.google.com/spreadsheets/d/1O_heFMJTlFbmsgIz_1Rao4XeVjqFY4sd80H7jX86H2w/edit?usp=sharing',
];
