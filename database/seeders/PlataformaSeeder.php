<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Pedido;
use App\Models\Rol;
use App\Models\User;
use App\Support\Excel;
use App\Support\ImportadorCursos;
use App\Support\RevisionCaptura;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Datos iniciales: usuarios, los cursos del catálogo (PDF «Cursos digitales 2026»)
 * y unos pedidos de ejemplo para ver cómo funciona el panel.
 */
class PlataformaSeeder extends Seeder
{
    private const BIO_PABLO_SOTO = 'Ingeniero Metalúrgico por la Universidad de Concepción, Chile. Su experiencia incluye 35 años de trabajo en importantes empresas tales como Fluor Daniel (Líder en Área de Procesos, División Tecnologías), CODELCO Chuquicamata (Ingeniero de Procesos), MINMETAL Ingeniería (Metalúrgico en Planta Concentradora El Teniente), Empresa Minera Candelaria (Jefe Área de Molienda), IM2 CODELCO (Jefe de Proyectos), ARA Worley Parsons (Gerente de Proyectos y Jefe de Disciplina Procesos) y LM Ing (Ingeniero Jefe de Proyectos). Adicionalmente ha sido docente en la Universidad de Concepción, Universidad Católica y BS Grupo.';

    public function run(): void
    {
        // ---------------- Usuarios ----------------
        $rol = fn (string $n) => Rol::where('nombre', $n)->value('id');
        User::create(['name' => 'Administrador', 'email' => 'admin@intermetperu.com', 'password' => 'admin12345', 'role_id' => $rol('Administrador')]);
        User::create(['name' => 'Ventas InterMet', 'email' => 'ventas@intermetperu.com', 'password' => 'ventas123', 'role_id' => $rol('Ventas')]);
        // Cliente de ejemplo (se registró en la web): solo ve la web y sus pedidos
        User::create(['name' => 'María Fernanda Quispe', 'email' => 'mquispe@gmail.com', 'celular' => '+51 987 111 222', 'pais' => 'Perú', 'password' => 'cliente123', 'role_id' => null]);

        // ---------------- Cursos (del PDF del catálogo) ----------------
        $cursos = json_decode(file_get_contents(database_path('data/cursos.json')), true);
        foreach ($cursos as $c) {
            Curso::create([
                'numero' => $c['numero'],
                'titulo' => $c['titulo'],
                'idioma' => $c['idioma'],
                'anio' => $c['anio'],
                'precio' => $c['precio'],
                'precio_oferta' => $c['precio_oferta'],
                'horas' => $c['horas'],
                'incluye' => $c['incluye'] === 'No' ? null : $c['incluye'],
                'instructor' => $c['instructor'],
                'instructor_bio' => $c['instructor'] === 'Ing. Pablo Soto' ? self::BIO_PABLO_SOTO : null,
                'link_pago' => $c['link_pago'],
                'publicado' => true,
            ]);
        }

        // Links de pago 2026 y enlaces de descarga (lista enviada por InterMet, cursos #1 al #19)
        $lista = database_path('data/links-pago-2026.txt');
        if (is_file($lista)) {
            (new ImportadorCursos(false))->importar(Excel::leer($lista, 'txt'));
        }

        // Curso 1 completo (como en la página actual)
        Curso::where('numero', 1)->first()?->update([
            'video_url' => 'https://youtu.be/DemzVmD19_Q',
            'destacado' => true,
            'resumen' => 'Aprenda a evaluar, operar y optimizar circuitos de flotación con ejercicios desarrollados a partir de datos reales de plantas en Perú y Chile.',
            'temario' => implode("\n", [
                'Revisión de conceptos generales. ¿Qué es hidrofobicidad e hidrofilidad?, ¿qué materiales flotan y cuáles no?, ¿cuáles variables se deben manejar?, ¿qué es un muestreo y cómo se ajusta a valores útiles?, ¿por qué se requieren reactivos y cuáles son? Ejercicios prácticos desarrollados en conjunto con el instructor (1 hora)',
                'Variables de flotación y su influencia en los resultados operacionales: tiempo de flotación, porcentaje de sólidos, dosis de reactivos, pH, aireación, granulometría, etc. Ejercicio práctico desarrollado con el instructor (1.5 horas)',
                'Optimización de los circuitos de flotación: control de datos operacionales, ajuste al modelo García-Zúñiga, técnica de los «Split Factors», ajustes por el método de mínimos de Legendre, determinación del tiempo óptimo de flotación. Ejercicios prácticos con el instructor (2 horas)',
                'Técnicas de evaluación y selección de condiciones de operación: evaluación de datos operacionales, necesidad y uso estadístico de datos en línea, acciones frente al empeoramiento de resultados operacionales (2 horas)',
                'Operación de circuitos de flotación: control de variables, mejoramiento continuo, disminución de tiempos muertos, cortocircuitos y recirculaciones dentro de la celda o columna (2.5 horas)',
                'Fundamentos fisicoquímicos de los reactivos: tipos de colectores y espumantes y sus usos según el mineral, modificadores de pH y su importancia, depresantes en flotación selectiva (1 hora)',
                'Ejercicios con el instructor: ajustes por Legendre, la flotación como ecuación cinética de primer orden, split factors en laboratorio y planta, optimización de circuitos y dimensionamiento de máquinas de flotación. Datos reales de plantas en Perú y Chile (2 horas)',
            ]),
        ]);

        // ---------------- Pedidos de ejemplo ----------------
        $this->pedido('María Fernanda Quispe', 'mquispe@gmail.com', '+51 987 111 222', 'Perú', [1, 2], 'niubiz', 'USD', 144, '07412589', 'por_verificar');
        $this->pedido('Jorge Andrés Rivas', 'jrivas@minera.cl', '+56 9 7654 3210', 'Chile', [94], 'niubiz', 'USD', 120, '913405', 'enviado',
            "https://we.tl/t-EJEMPLO123\nhttps://we.tl/t-EJEMPLO456");
        $this->pedido('Luis Huamán', 'lhuaman@hotmail.com', '+51 955 333 444', 'Perú', [63], 'niubiz', 'USD', 50, '55512345', 'rechazado');
    }

    private function pedido(string $nombre, string $email, string $cel, string $pais, array $numeros, string $metodo, string $moneda, float $monto, string $op, string $estado, ?string $enlaces = null): void
    {
        $cursos = Curso::whereIn('numero', $numeros)->where('idioma', 'es')->get();
        $captura = $this->capturaEjemplo($metodo, $monto, $moneda, $op, $nombre);
        $p = Pedido::create([
            'user_id' => User::where('email', $email)->whereNull('role_id')->value('id'),
            'estado' => $estado, 'nombre' => $nombre, 'email' => $email, 'celular' => $cel, 'pais' => $pais,
            'total' => $cursos->sum('precio_actual'), 'pago_metodo' => $metodo, 'pago_moneda' => $moneda, 'pago_monto' => $monto,
            'pago_fecha' => now()->toDateString(), 'pago_operacion' => $op, 'pago_operacion_norm' => Pedido::operacionesNorm($op),
            'pago_captura' => $captura, 'pago_captura_hash' => hash('sha256', Storage::disk('local')->get($captura) . $op),
            'pago_alertas' => ['avisos' => [], 'archivo' => ['tipo' => 'imagen', 'ancho' => 600, 'alto' => 900]],
            'motivo_rechazo' => $estado === 'rechazado' ? 'El monto de la constancia (USD 50) no corresponde al precio del curso (USD 300).' : null,
            'enlace_descarga' => $enlaces,
            'verificado_en' => in_array($estado, ['pagado', 'enviado']) ? now() : null,
            'enviado_en' => $estado === 'enviado' ? now() : null,
        ]);
        foreach ($cursos as $c) {
            $p->items()->create(['curso_id' => $c->id, 'numero' => $c->numero, 'titulo' => $c->titulo, 'precio' => $c->precio_actual]);
            if ($estado === 'enviado') {
                $c->increment('vendidos');
            }
        }
    }

    /** Constancia de ejemplo dibujada (solo para ver el panel funcionando) */
    private function capturaEjemplo(string $metodo, float $monto, string $moneda, string $op, string $nombre): string
    {
        $img = imagecreatetruecolor(600, 900);
        $fondo = imagecolorallocate($img, 255, 255, 255);
        $azul = imagecolorallocate($img, 13, 59, 140);
        $gris = imagecolorallocate($img, 90, 90, 90);
        imagefill($img, 0, 0, $fondo);
        imagefilledrectangle($img, 0, 0, 600, 140, $azul);
        $fuente = 5;
        imagestring($img, $fuente, 30, 55, 'CONSTANCIA DE EJEMPLO - ' . strtoupper($metodo), imagecolorallocate($img, 255, 255, 255));
        $lineas = [
            'Monto: ' . ($moneda === 'PEN' ? 'S/ ' : 'USD ') . number_format($monto, 2),
            'Fecha: ' . now()->format('d/m/Y H:i'),
            'Numero de operacion: ' . $op,
            'Ordenante: ' . iconv('UTF-8', 'ASCII//TRANSLIT', $nombre),
            'Beneficiario: ' . iconv('UTF-8', 'ASCII//TRANSLIT', config('cursos.titular')),
        ];
        foreach ($lineas as $i => $l) {
            imagestring($img, $fuente, 30, 200 + $i * 50, $l, $i === 0 ? $azul : $gris);
        }
        ob_start();
        imagepng($img);
        $ruta = 'capturas-pago/ejemplo-' . $op . '.png';
        Storage::disk('local')->put($ruta, ob_get_clean());
        imagedestroy($img);

        return $ruta;
    }
}
