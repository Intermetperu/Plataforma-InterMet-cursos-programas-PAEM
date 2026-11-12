# Plataforma de cursos digitales · InterMet (Laravel 13)

Tienda de los cursos y congresos digitales, hecha con la **plantilla del sistema del hotel**:
web pública con la plantilla Ecorik y panel de administración Rocker (Bootstrap 5).
La «reserva» del hotel ahora es **«Registrar pago»**: el cliente paga el curso **con su link de pago (Niubiz)**,
registra el pago con la constancia, ustedes lo verifican y le envían los enlaces de descarga.

> **Solo se paga con link.** Cada curso tiene su propio link de pago Niubiz; al tocar **«Comprar ahora»** se abre el
> formulario de pago de Niubiz de ese curso. No hay transferencias, Yape ni pagos en soles.
>
> **¿Ya tenía la plataforma instalada?** Corra `php artisan migrate`: carga los links 2026 de los cursos #01 al #19 y
> los enlaces de descarga sin borrar sus pedidos.

## Instalar en Laragon (Windows)

1. Copie la carpeta `plataforma` en `C:\laragon\www\`.
2. En **HeidiSQL** (o phpMyAdmin) cree la base de datos `plataforma` (utf8mb4).
3. En la terminal de Laragon, dentro de la carpeta:

   ```
   composer install
   npm install
   npm run build
   php artisan migrate:fresh --seed
   php artisan serve
   ```

4. Web: `http://127.0.0.1:8000` · Iniciar sesión (clientes y panel): `http://127.0.0.1:8000/login`

> ¿Prefiere importar? `database/plataforma.sql` tiene la base completa (176 cursos del PDF y 3 pedidos de ejemplo).
> El archivo `.env` ya apunta a MySQL de Laragon (`DB_DATABASE=plataforma`, usuario `root` sin clave).

### Compartir la web en su red (WiFi / oficina)
Doble clic en **`COMPARTIR-EN-RED.bat`** (en la carpeta `plataforma`). Le muestra el link para compartir
(por ejemplo `http://192.168.1.45:8000`), abre el puerto en el firewall y deja la web prendida. No cierre esa ventana.

### Compartir la web por internet (con alguien que está en otro lugar)
Doble clic en **`COMPARTIR-POR-INTERNET.bat`**. La primera vez descarga un programa gratuito de Cloudflare (unos 60 MB)
y luego muestra un link como `https://palabra-palabra-palabra.trycloudflare.com` (ya queda **copiado**: péguelo en WhatsApp
o en el correo). Funciona desde cualquier lugar y celular, con https.
- La computadora debe quedar **encendida** y la ventana **abierta** mientras la otra persona use la web.
- El link **cambia** cada vez que lo abre. Es para mostrar o probar; para dejar la web publicada siempre, use un hosting.
- Antes de compartir, **cambie las contraseñas de prueba** (cualquiera con el link puede ver la página de ingreso).

### Programas de alta especialización (PAEM) y carrito
- **Ventana de bienvenida:** al entrar a la portada aparece una ventana que presenta los programas (los 3 de mayor
  ahorro y el botón «Ver los 38 programas»). Sale al entrar a la web y cada vez que se recarga la portada (no al navegar dentro de la web);
  el asistente espera a que la cierren.
  Se activa o desactiva en Configuración › Textos de la web.
- **Página propia de los programas:** `/programas` (menú «Programas» o «Ver los 38 programas» en la portada). Tiene
  buscador (también busca dentro de los cursos e instructores), filtros por área y orden por precio, cursos o ahorro.
- En la portada, la sección **Programas de alta especialización** muestra los **38 programas** en tarjetas (con flechas
  para pasar). Cada tarjeta abre la página del programa: galería con las imágenes de sus cursos, precio (con el ahorro
  frente a comprar los cursos por separado), **Comprar ahora**, **Agregar al carrito** y la lista de cursos que incluye.
- **Carrito** (ícono arriba a la derecha): el cliente junta varios programas, paga cada uno con su botón «Pagar»
  (link de pago) y registra su pago: los programas del carrito ya van marcados en «Registrar pago».
  Si un programa aún no tiene link, puede comprarlo por WhatsApp desde el carrito.
- **Panel › Programas (PAEM):** pegue el **link de pago** de cada programa, cambie precio, cursos (N° separados por comas),
  color, ícono, área, orden, y muestre u oculte cada programa. También:
  - **Temario:** un tema por línea; la línea que empieza con `#` es el título de un módulo. Si se deja vacío, la web
    muestra un módulo por cada curso incluido.
  - **Portada:** la primera imagen de la galería del programa.
  - **Miniaturas:** hasta 20 fotos para la galería (se ordenan con un número y se quitan con «Quitar»). Se puede elegir
    si además se muestran las fotos de sus cursos.
- En el pedido, cada programa sale con la etiqueta **PAEM** y la lista de sus cursos; al enviar el acceso se proponen
  los enlaces de descarga de todos esos cursos.
- Instalación: después de copiar los archivos, ejecute una vez `php artisan migrate` (crea los 38 programas, sus áreas
  y los cursos 177 a 181).

### Importar las imágenes de los cursos (ZIP)
El ZIP **`Imagenes-cursos-InterMet.zip`** trae una imagen por curso. Cada archivo empieza con el **N°** y el **idioma**
del curso en la plataforma (ej.: `003-es-dimensionamiento….jpg` = curso N° 3 en español).
- **Desde el panel:** Cursos › Importar / exportar Excel › «Imágenes de los cursos (ZIP)» › suba el ZIP › Importar.
- **Si el panel dice que el archivo pesa mucho** (límite de PHP): copie el ZIP a la carpeta `plataforma` y haga doble clic
  en **`IMPORTAR-IMAGENES.bat`** (o en la terminal: `php artisan cursos:importar-imagenes`).
- No cambia los cursos que ya tienen imagen, salvo que marque «Cambiar también las imágenes que ya tienen»
  (en la terminal: `--reemplazar`). Los archivos de la carpeta `videos-youtube` no se importan.
- Para cambiar una imagen después: reemplace el archivo en el ZIP con el mismo nombre y vuelva a importarlo marcando «Cambiar…».

### Traer las imágenes de los cursos desde intermetperu.com
Doble clic en **`TRAER-IMAGENES-INTERMET.bat`** (o en la terminal: `php artisan cursos:imagenes --asignar`). Necesita internet.
1. Lee «Cursos digitales», «Nuevos cursos» y la versión en inglés de intermetperu.com y toma la imagen de cada curso
   (la original, en tamaño completo).
2. Las guarda en **`Imagenes-cursos-InterMet.zip`** (en la carpeta `plataforma`) con un `indice.csv` (qué imagen es de
   qué curso) y `asignaciones.csv` (a qué curso de la plataforma se le puso).
3. Se la pone a cada curso de la plataforma comparando los títulos (también el N°, el año y el idioma; la versión en
   inglés usa la misma imagen si la web no tiene una propia). Las guarda en `public/uploads/cursos`, reducidas a 1280 px.
- No toca los cursos que ya tienen imagen. Para cambiarlas también: `php artisan cursos:imagenes --asignar --reemplazar`.
- Para ver antes qué imagen recibiría cada curso, sin guardar nada: `php artisan cursos:imagenes --simular`.
- Solo el ZIP, sin tocar los cursos: `php artisan cursos:imagenes`.
- Los cursos que no tienen imagen en la web siguen con su portada automática; se les puede subir una en Admin › Cursos.

### Usuarios

| Usuario | Contraseña | Rol | Qué puede hacer |
|---|---|---|---|
| admin@intermetperu.com | admin12345 | Administrador | Todo |
| ventas@intermetperu.com | ventas123 | Ventas | Revisar pagos, enviar accesos, ver cursos |

| mquispe@gmail.com | cliente123 | Cliente (de ejemplo) | Solo la web y «Mis pedidos» |

### Iniciar sesión y registrarse
- En la web, arriba a la derecha: **Iniciar sesión** y **Registrarse** (en el celular, en el menú y en la barra de arriba).
- **Cualquier persona se puede registrar** (nombre, correo, celular, país y contraseña). Después de registrarse o de
  iniciar sesión **vuelve a la web**, a la página donde estaba. Su nombre aparece arriba, sus datos se llenan solos al
  registrar un pago y ve todos sus pedidos en **Mis pedidos**. Un cliente **nunca entra al panel**.
- Con las credenciales del **personal** (Administrador, Ventas…), el mismo «Iniciar sesión» **lleva al panel de administración**.
- En el panel, Usuarios y roles › **Clientes** lista a los que se registraron (con su WhatsApp y sus pedidos).

Cambie las contraseñas antes de publicar (menú del usuario › Mi contraseña, o Usuarios y roles).

## La web pública

### Catálogo (`/` y `/cursos-digitales`)
- Portada con buscador (por tema, instructor o N°) y los 3 pasos para comprar.
- **Sin categorías: filtro por año en que salió el curso** (2025, 2024, 2023… con la cantidad de cada año),
  idioma (Español / English) y orden: N° de catálogo (por defecto: #01, #02…), más recientes, más antiguos y precio.
- **Paginación**: 12 cursos por página (se cambia en Configuración › Textos de la web).
- Cada tarjeta: video de YouTube (se abre en una ventana), foto, o **portada automática** con el color del tema
  (flotación, molienda, muestreo, hidrometalurgia, relaves, oro, cobre, simulación…), sellos Nuevo / Congreso / English / Oferta,
  N°, título, instructor, horas, software incluido, precio y botón **Comprar ahora**: abre el formulario de pago de Niubiz
  del curso en otra pestaña y, en esta, lleva al detalle del curso con el aviso «Cuando termine de pagar, registre su pago aquí».
  Los cursos que todavía no tienen link muestran «Ver curso» y, en su página, «La compra en línea estará disponible muy pronto».

### Programas de alta especialización (portada)
- Cuatro tarjetas de colores: **Flotación de Minerales, Aguas y Relaves, Minería 5.0 y Excelencia Operacional**.
- «Más detalles» abre una ventana **«Próximamente»** («Nuestro equipo está preparando este programa…») con el botón
  «Quiero que me avisen» por WhatsApp. Cuando cada programa tenga su página, ponga el link en
  `config/cursos.php` › `programas` › `enlace` (ahí también se cambian nombres, colores e íconos).

### Detalle del curso (clic en el título)
- Video de presentación, «Dirigido a», instructor con su trayectoria y **temario** con la duración de cada tema.
- A la derecha, **Inversión USD …** y «¿Cómo acceder al curso?»:
  1. **Comprar ahora** (formulario de pago Niubiz del curso). En el celular el botón queda fijo abajo.
  2. **Registrar mi pago**.
  3. Recibir los enlaces por correo.
- Botón de WhatsApp con el nombre del curso, cursos relacionados y, si existe, el enlace a la versión en inglés.
- Las direcciones son como las de la web actual: `/cursos-digitales/1-optimizacion-en-plantas-de-flotacion`.

### Registrar pago (`/registrar-pago`)
- Cursos que pagó (uno o varios), sus datos, monto en USD (se calcula solo), fecha, n.º de operación o pedido de Niubiz
  (si pagó varios links, los separa con comas) y la constancia (captura del voucher o del correo de Niubiz).
- **Protección contra constancias falsas** (la misma del hotel): rechaza archivos que no son imagen o PDF, la misma
  constancia o el mismo n.º de operación de otro pedido, y avisa si la imagen fue editada o se ve igual a otra.
- Al enviar, el cliente ve **su pedido** (C-000123) con el avance: pago registrado → verificado → enlaces enviados.
  Lo puede consultar en **Mi pedido** con el código y su correo.

### Asistente (chat en la parte lateral)
- Botón **«¿Te ayudo?»** abajo a la derecha. Al abrirlo sale un **panel lateral** (en el celular ocupa toda la pantalla).
- **Aparece solo** al entrar a la web (una vez por visita, a los 2 segundos): en la PC el panel entra deslizándose con
  rebote, brillo y una manito que saluda, y el mensaje se escribe letra por letra; en el celular sale una tarjeta de
  saludo junto al botón. No aparece solo en iniciar sesión, registrarse ni registrar pago. Se cambia en Configuración.
- Responde con los datos reales de la plataforma, sin costo: busca cursos por **tema, instructor, N°, año o idioma**
  (también en inglés: «flotation», «gold»…), da el **precio**, explica **cómo comprar**, el **envío**, el **certificado**,
  precios para **empresas**, el **horario** y el **contacto**. Muestra los cursos como tarjetas con su precio y enlace.
- En la página de un curso sabe qué curso está viendo («¿Qué incluye este curso?», «¿Cómo lo compro?» con su link de pago).
- Recuerda la conversación mientras el cliente navega por la web. Si no sabe algo, ofrece **WhatsApp**.
- Se configura en **Configuración › Asistente (chat)**: mostrar u ocultar, nombre y mensaje de bienvenida.
- **Opcional, con IA:** agregue `ANTHROPIC_API_KEY=...` en el `.env` (se paga por uso en console.anthropic.com) y elija
  «Sí, redactar con IA». La IA responde con los mismos datos de la plataforma y, si falla, el asistente usa las
  respuestas automáticas. Modelo por defecto: `CHATBOT_MODELO=claude-haiku-5-5`.
- Límite: 20 preguntas por minuto por visitante (contra abuso).

## El panel

- **Tablero**: pagos por verificar, ventas del mes y totales, ventas de los últimos 6 meses, cursos más vendidos.
- **Pedidos**: por verificar (los más antiguos primero), pagados, enviados y rechazados. En cada pedido:
  - La constancia y la **lectura automática** (monto, n.º de operación, comercio y fecha) en verde o rojo.
    Antes de enviar, confirme el pago en su panel de Niubiz.
  - **Verificar y enviar el acceso**: los enlaces de descarga **ya vienen puestos** desde cada curso (columnas VÍDEOS y
    MATERIALES de su lista). Revíselos, agregue un mensaje si quiere y envíe: le llega un correo al cliente y los ve en
    su pedido. También hay un botón para enviárselos por **WhatsApp**.
  - **Rechazar** con motivo (le llega al cliente) y «Volver a por verificar» si fue un error.
- **Cursos**: lista con filtros (sin link de pago, sin video, sin temario, en oferta, ocultos), mostrar u ocultar en la web,
  y el formulario completo: N°, título, año, idioma, horas, instructor y su trayectoria, dirigido a, temario,
  precio regular y de oferta, **link de pago**, **enlaces de descarga** (privados, no se ven en la web), video de YouTube o foto de portada.
- **Importar / exportar Excel** (para los links de pago):
  - **Desde Google Sheets**: ya viene puesto el link de su hoja. Cuando agregue links en la hoja, toque
    **«Actualizar desde la hoja»** y se cargan solos. La hoja debe estar compartida como «Cualquier persona con el enlace».
  - Suba **su misma lista** (.xlsx, .csv o .txt) con las columnas N°, título, año, expositor, precio, VÍDEOS, MATERIALES,
    LINK DE PAGO, LINKS DE PAGO 2026. Si hay dos columnas de link, **se usa la última** (la de 2026).
    VÍDEOS y MATERIALES quedan como enlaces de descarga del curso.
  - O **Descargar cursos en Excel** → complete «Link de pago» y «Enlaces de descarga» → **Importar**.
  - Se reconoce el curso por su N° (o por el título). Solo se cambian las celdas con datos; los cursos nuevos se crean si tienen precio.
- **Reportes**: ventas por mes y por curso, con exportación a Excel.
- **Configuración**: empresa y contacto (WhatsApp, correos, horario, redes), pagos (nombre del comercio que sale en el
  voucher de Niubiz: «COMINSAC InterMet») y textos de la web («Todos los cursos incluyen», «Dirigido a» general, cursos por página).
- **Usuarios y roles**: Administrador, Ventas, Contenido (o los que cree).

## Correos

Por defecto `MAIL_MAILER=log`: los correos **no salen**, se guardan en `storage/logs/laravel.log` (el panel lo avisa y
ofrece WhatsApp). Para enviarlos de verdad, ponga en `.env` los datos de su correo. Ejemplo con Gmail
(use una «contraseña de aplicación» de Google):

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=digital@intermetperu.com
MAIL_PASSWORD=la-contraseña-de-aplicacion
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="digital@intermetperu.com"
MAIL_FROM_NAME="InterMet Cursos"
```

Se envían: «Recibimos su pago» (al cliente), «Nuevo pago por verificar» (al correo de pedidos), los enlaces de descarga
y el aviso de rechazo.

## Datos cargados

- Los **176 cursos del PDF «Cursos digitales 2026»** (187 filas, porque algunos tienen versión en inglés con el mismo N°):
  título, año, precio de oferta y regular, software incluido e instructor.
- La web muestra el **precio regular** (como hoy). Para mostrar el de oferta, active «Mostrar el precio de oferta» en el curso.
- El curso #01 tiene todo como en la web actual (video, temario, trayectoria del Ing. Pablo Soto).
- **Los cursos #01 a #43 ya tienen su link de pago 2026 (COMINSAC)** (`database/data/links-pago-2026.txt`, tomado de la hoja 1 de su Google Sheets). Del #01 al #19 también con su
  **link de pago 2026 (COMINSAC)** y sus enlaces de VÍDEOS / MATERIALES. **Los demás links se cargan con su lista**
  en Cursos › Importar / exportar Excel (cuando la complete, súbala igual).
- **Ojo:** los enlaces de WeTransfer (we.tl) **vencen** en pocos días. Cuando renueve uno, cámbielo en el curso
  (o vuelva a importar la lista) para que los próximos pedidos salgan con el enlace nuevo.
- Los cursos sin temario muestran el botón «Pedir temario» por WhatsApp.
- Se corrigieron algunos errores de tipeo del PDF (por ejemplo «APLUCACION» → «APLICACIÓN», «SCENTIAL» → «ESSENTIAL»).

## Antes de publicar
1. Cambie las contraseñas de prueba.
2. Configuración: revise WhatsApp, correos y el nombre del comercio.
3. Importe su lista con los links de pago de los demás cursos.
4. Configure el correo (arriba) y ponga `APP_DEBUG=false` y su dominio en `APP_URL`.
5. El logo oficial está en `public/assets/img/logo-intermet.png` (versión blanca para fondos oscuros: `logo-intermet-blanco.png`; ícono: `icono-intermet.png`).
