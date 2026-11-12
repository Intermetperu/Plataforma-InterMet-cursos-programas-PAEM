# InterMet · Cursos y congresos digitales de minería

Plataforma web para vender los cursos y congresos digitales de InterMet y los **Programas de Alta Especialización Minera (PAEM)**.
El cliente elige, paga con su **link de pago (Niubiz)**, registra su pago con la constancia y recibe los enlaces de descarga en su correo.

![Portada](docs/capturas/01-portada.jpg)

## Funciones principales

- **Catálogo** de cursos y congresos digitales con buscador, filtro por año e idioma, y página propia de cada curso.
- **38 programas de alta especialización (PAEM)**, cada uno con su página: galería, temario por módulos, precio con el ahorro y los cursos que incluye.
- **Carrito**: el cliente puede llevar varios programas y pagar cada uno con su link.
- **Ventana de bienvenida** que presenta los programas al entrar a la web.
- **Asistente (chat)** que responde solo sobre los cursos y congresos digitales de minería.
- **Panel de administración**: pedidos, verificación de pagos, cursos, programas (temario, portada, miniaturas, link de pago), filtros con o sin link de pago, usuarios y configuración.
- Diseño adaptado a **celular**.

## Capturas

### Ventana de bienvenida
![Ventana de bienvenida](docs/capturas/02-ventana-programas.jpg)

### Programas en la portada
![Programas en la portada](docs/capturas/03-programas-en-portada.jpg)

### Catálogo de cursos y congresos
![Catálogo](docs/capturas/04-catalogo.jpg)

### Página de los programas de alta especialización
![Página de programas](docs/capturas/05-pagina-programas.jpg)

### Página de un programa
![Página de un programa](docs/capturas/06-programa.jpg)

### Carrito
![Carrito](docs/capturas/07-carrito.jpg)

### Asistente
![Asistente](docs/capturas/08-asistente.jpg)

### En el celular
![Celular](docs/capturas/09-celular.jpg)

### Panel · Programas
![Panel de programas](docs/capturas/10-panel-programas.jpg)

### Panel · Editar programa
![Editar programa](docs/capturas/11-panel-editar-programa.jpg)

## Tecnología

Laravel 13 (PHP) · MySQL / MariaDB · Blade · Vite · Bootstrap 5

## Instalación

```
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

La guía completa (Laragon, compartir en red o por internet, importar imágenes, panel) está en **[INSTRUCCIONES.md](INSTRUCCIONES.md)**.

---
© InterMet Perú. Todos los derechos reservados.
