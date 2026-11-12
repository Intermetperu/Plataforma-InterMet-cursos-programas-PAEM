<?php

namespace App\Services;

use App\Models\Curso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asistente de la web pública.
 *
 * Responde con los datos reales de la plataforma: el catálogo de cursos (búsqueda por tema,
 * instructor, N°, año e idioma) y los textos de Admin › Configuración (compra, envío, contacto…).
 * Funciona sin ningún servicio externo. Si en el .env hay ANTHROPIC_API_KEY y en Configuración
 * se activa «Respuestas con IA», redacta la respuesta con Claude usando esos mismos datos;
 * si la IA falla, contesta igual con las respuestas automáticas.
 */
class Chatbot
{
    /** Palabras que no sirven para buscar cursos */
    private const VACIAS = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al', 'a', 'en', 'y', 'o', 'u', 'e', 'que', 'q', 'qué',
        'quiero', 'queria', 'quisiera', 'me', 'te', 'se', 'lo', 'le', 'les', 'nos', 'por', 'para', 'con', 'sin', 'sobre', 'como',
        'cual', 'cuales', 'donde', 'cuando', 'cuanto', 'cuanta', 'cuantos', 'hay', 'tienen', 'tiene', 'tienes', 'tengo', 'busco',
        'buscando', 'buscar', 'necesito', 'informacion', 'info', 'curso', 'cursos', 'digital', 'digitales', 'hola', 'buenas',
        'buenos', 'dias', 'tardes', 'noches', 'gracias', 'favor', 'porfavor', 'porfa', 'puedo', 'puede', 'pueden', 'podria',
        'es', 'son', 'esta', 'este', 'estos', 'estas', 'esa', 'ese', 'eso', 'mi', 'mis', 'su', 'sus', 'tu', 'tus', 'algo',
        'algun', 'alguno', 'alguna', 'mas', 'muy', 'bien', 'ver', 'saber', 'dar', 'dame', 'den', 'si', 'no', 'ya', 'tema',
        'temas', 'ustedes', 'usted', 'interesa', 'interesado', 'interesada', 'ofrecen', 'venden', 'tambien', 'otro', 'otros',
        'otra', 'alguien', 'todo', 'todos', 'ok', 'okey', 'bueno', 'sobre', 'acerca', 'relacionado', 'relacionados', 'aprender',
        'estudiar', 'llevar', 'tomar', 'hacer', 'mejor', 'mejores', 'bueno', 'buena', 'the', 'of', 'and', 'for', 'in', 'about',
        'course', 'courses', 'want', 'need', 'some', 'any', 'do', 'you', 'have', 'is', 'are', 'what', 'how', 'much', 'i',
        'programa', 'programas', 'especializacion', 'material', 'video', 'videos', 'grabacion', 'esto', 'ahi', 'aqui',
        'hazme', 'dime', 'explicame', 'ayudame', 'dame', 'cuentame', 'sabes', 'conoces', 'nuevo', 'nuevos', 'nueva', 'nuevas', 'reciente', 'recientes', 'ultimo', 'ultimos', 'ingles', 'english', 'espanol',
        'congreso', 'congresos', 'conferencia', 'conferencias', 'recomiendas', 'recomienda', 'recomiendan', 'recomendar',
        'recomendacion', 'sugieres', 'sugerencia', 'empezar', 'principiante', 'ano', 'anio', 'numero', 'nro', 'n',
        'minero', 'mineros', 'minera', 'mineras', 'metalurgico', 'metalurgicos', 'metalurgica', 'ingeniero', 'ingenieros', 'ingenieria',
        'llego', 'llega', 'pague', 'compre', 'tipo', 'area', 'sector', 'industria', 'proceso', 'procesos',
    ];

    /** Palabras de la compra y la atención: no son «ajenas» aunque no salgan en los cursos */
    private const ATENCION = [
        'yape', 'plin', 'visa', 'mastercard', 'niubiz', 'paypal', 'banco', 'bancos', 'bcp', 'bbva', 'interbank', 'scotiabank', 'cuenta',
        'cuentas', 'deposito', 'transferencia', 'efectivo', 'moneygram', 'western', 'union', 'extranjero', 'fuera', 'pais', 'chile',
        'bolivia', 'mexico', 'colombia', 'ecuador', 'argentina', 'venezuela', 'brasil', 'espana', 'peru', 'lima', 'arequipa', 'factura',
        'boleta', 'empresa', 'certificado', 'diploma', 'constancia', 'voucher', 'comprobante', 'captura', 'pedido', 'codigo', 'correo',
        'gmail', 'hotmail', 'outlook', 'descarga', 'descargar', 'wetransfer', 'enlace', 'drive', 'zoom', 'vivo', 'grabado', 'video',
        'computadora', 'laptop', 'ayuda', 'ayudar', 'ayudame', 'consulta', 'pregunta', 'duda', 'dudas', 'hago',
        'hacer', 'quiero', 'necesito', 'saber', 'problema', 'error', 'pagina', 'web', 'usuario', 'contrasena', 'clave', 'registro',
        'registrarme', 'inscribirme', 'sabado', 'domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'feriado', 'hoy', 'manana',
        'ahora', 'rapido', 'urgente', 'tiempo', 'semana', 'minutos', 'gratis', 'gratuito', 'beca', 'estudiante', 'estudiantes',
        'universidad', 'personas', 'participantes', 'grupo', 'equipo', 'colegas', 'trabajo', 'listo', 'perfecto', 'genial', 'excelente',
        'entendido', 'claro', 'hello', 'thanks', 'please', 'disponible', 'disponibles', 'tienda', 'intermet', 'cursito', 'saludos',
        'llamada', 'llamar', 'escribir', 'mensaje', 'responder', 'respuesta', 'asistente', 'robot', 'chat',
    ];

    /** Sinónimos para que encuentre el curso aunque esté en otro idioma o con otra palabra */
    private const SINONIMOS = [
        'flotacion' => ['flotaci', 'flotation', 'celdas'],
        'flotation' => ['flotaci', 'flotation'],
        'molienda' => ['molienda', 'conminuc', 'comminution', 'grinding', 'chancad', 'sag'],
        'chancado' => ['chancad', 'conminuc', 'crushing'],
        'conminucion' => ['conminuc', 'comminution', 'molienda'],
        'oro' => ['oro', 'gold ', 'aurifer', 'carbon activado', 'cianur'],
        'plata' => ['plata', 'silver'],
        'cobre' => ['cobre', 'copper'],
        'litio' => ['litio', 'lithium'],
        'relaves' => ['relave', 'tailings'],
        'relave' => ['relave', 'tailings'],
        'agua' => ['agua', 'water'],
        'aguas' => ['agua', 'water'],
        'muestreo' => ['muestreo', 'sampling'],
        'lixiviacion' => ['lixivia', 'leaching'],
        'hidrometalurgia' => ['hidrometal', 'hydrometal', 'lixivia', 'electrometal'],
        'estadistica' => ['estadist', 'minitab', 'statistic', 'experimentos'],
        'simulacion' => ['simulac', 'metsim', 'simulation', 'software'],
        'ia' => ['machine learning', 'inteligencia artificial', 'artificial intelligence', 'digital'],
        'inteligencia' => ['machine learning', 'inteligencia artificial', 'digital'],
        'artificial' => ['machine learning', 'inteligencia artificial'],
        'machine' => ['machine learning'],
        'mineria' => ['miner', 'mining'],
        'geometalurgia' => ['geometal', 'geometallurg'],
        'seguridad' => ['seguridad', 'safety'],
        'mantenimiento' => ['mantenimiento', 'maintenance'],
        'planta' => ['planta', 'plant'],
        'plantas' => ['planta', 'plant'],
        'espesamiento' => ['espesa', 'thicken'],
        'filtracion' => ['filtrac', 'filtration'],
        'pulpas' => ['pulpa', 'slurr'],
    ];

    /** Temas para el botón «Buscar un curso»: [texto que se muestra, lo que se busca] */
    private const TEMAS = [
        ['Flotación', 'flotación'], ['Molienda y chancado', 'molienda'], ['Hidrometalurgia', 'hidrometalurgia'],
        ['Oro y plata', 'oro'], ['Cobre', 'cobre'], ['Relaves y aguas', 'relaves'], ['Muestreo', 'muestreo'],
        ['Machine Learning e IA', 'inteligencia artificial'], ['Simulación (MetSim)', 'simulación'], ['Estadística', 'estadística'],
    ];

    private string $texto = '';

    private ?Curso $cursoPagina = null;

    /** La pregunta no tiene que ver con los cursos (ej.: «lampa», «medicina») */
    private bool $fueraDeTema = false;

    /** Palabra sin tildes => como la escribió el visitante */
    private array $escrito = [];

    /**
     * @param  array{curso?: ?string, ultimos?: array, historial?: array}  $contexto
     * @return array{texto: string, cursos: array, botones: array, ver_mas: ?array, ia?: bool}
     */
    public function responder(string $mensaje, array $contexto = []): array
    {
        $this->texto = ' ' . preg_replace('/[^a-z0-9#ñ]+/', ' ', Str::lower(Str::ascii($mensaje))) . ' ';
        $this->escrito = [];
        preg_match_all('/[\p{L}\p{N}]+/u', $mensaje, $m);
        foreach ($m[0] as $palabra) {
            $this->escrito[Str::lower(Str::ascii($palabra))] = mb_strtolower($palabra);
        }
        $this->cursoPagina = ! empty($contexto['curso']) ? Curso::publicados()->where('slug', $contexto['curso'])->first() : null;

        $this->fueraDeTema = false;
        $base = $this->reglas($contexto);

        if ($this->iaActiva() && ! $this->fueraDeTema) {
            try {
                return $this->conIa($mensaje, $contexto, $base);
            } catch (Throwable $e) {
                Log::warning('Chatbot: la IA no respondió, se usa la respuesta automática', ['error' => $e->getMessage()]);
            }
        }

        return $base;
    }

    public function iaActiva(): bool
    {
        return (bool) config('services.anthropic.key') && (string) config('cursos.chatbot_ia') === '1';
    }

    /* =====================================================================
       Respuestas automáticas
       ===================================================================== */

    private function reglas(array $contexto): array
    {
        $t = $this->texto;
        $palabras = count(array_filter(explode(' ', trim($t))));
        $busqueda = $this->buscar();
        $hay = $busqueda['total'] > 0;
        $esteCurso = $this->cursoPagina && ($this->tiene('este|esta|ese|el curso|del curso|lo compro|lo pago|incluye|cuanto cuesta|precio|dura|horas|temario|instructor|quien dicta') && ! $hay);

        // Sobre un curso que ya se mostró: «¿y cuánto cuesta?», «¿cómo lo compro?»
        $previos = ! $hay && ! $esteCurso && ! empty($contexto['ultimos'])
            ? Curso::publicados()->whereIn('id', $ids = array_slice(array_map('intval', (array) $contexto['ultimos']), 0, 3))->get()->sortBy(fn ($c) => array_search($c->id, $ids))->values()
            : collect();

        // 1) Hablar con una persona
        if ($this->tiene('asesor\w*|humano|persona real|una persona|hablar con|llamar\w*|whatsapp|wsp|telefono|su celular|tu celular|numero de celular|al celular|numero de contacto|contacto|contactar\w*|su correo|tu correo|cual es el correo|correo de contacto')) {
            return $this->contacto();
        }

        // Horario de atención
        if ($this->tiene('horario\w*|atienden|atencion|hora de atencion|abren|a que hora\w*')) {
            return $this->r(
                'Nuestro horario de atención es **' . config('cursos.horario') . "**. Pero la web funciona las 24 horas: puedes comprar y registrar tu pago cuando quieras.",
                botones: [$this->whatsapp(null, 'Escribir por WhatsApp')],
            );
        }

        // 2) Ya pagó / dónde está su curso
        if ($this->tiene('ya pague|ya hice el pago|ya realice|ya compre|registr\w* (el |mi )?pago|constancia|voucher|comprobante|captura')) {
            return $this->r(
                "¡Perfecto! El siguiente paso es **registrar tu pago**: completa tus datos, marca el curso y adjunta la constancia o captura del pago.\n\nApenas lo verifiquemos te enviamos los enlaces de descarga a tu correo.",
                botones: [$this->enlace('Registrar mi pago', route('pedido.create'), 'bx-upload'), $this->pregunta('¿Cuándo recibo mi curso?')],
            );
        }
        if ($this->tiene('mi pedido|mis pedidos|estado de mi|seguimiento|codigo de pedido|no (me )?(ha )?lleg\w*|no recibi\w*|aun no|todavia no|cuando (me )?(llega|llegan) mi')) {
            return $this->r(
                "Puedes ver en qué va tu pedido con el **código** que te llegó al correo (por ejemplo C-000123) y el correo con el que lo registraste.\n\nSi ya pasaron más de 24 horas hábiles, escríbenos y lo revisamos al momento.",
                botones: [$this->enlace('Consultar mi pedido', auth()->check() ? route('pedido.mios') : route('pedido.consulta'), 'bx-search-alt'), $this->whatsapp('Tengo una consulta sobre mi pedido.')],
            );
        }

        // Algo que no tiene que ver con los cursos de minería («lampa», «medicina», «receta»…)
        if (! $hay && ! $esteCurso && $ajenas = $this->palabrasAjenas()) {
            return $this->fueraDeTema($ajenas);
        }

        // 3) Cómo se recibe el curso
        if ($this->tiene('descarg\w*|wetransfer|cuando (me )?(llega|llegan|recibo|envian|mandan)|como (me )?(llega|llegan|recibo|lo recibo|envian)|recib\w*|envio|entrega\w*|acceso')) {
            return $this->r(
                config('cursos.envio') . "\n\nNo es un curso en vivo: recibes la **grabación completa en MP4** con el material de trabajo, para verla cuando quieras y las veces que quieras.",
                botones: [$this->pregunta('¿Cómo compro un curso?'), $this->enlace('Consultar mi pedido', route('pedido.consulta'), 'bx-search-alt')],
            );
        }

        // 4) Cómo comprar / pagar
        if ($this->tiene('compr(ar|o|a|as|e|en)|como (lo )?adquiero|adquirir|pag(ar|o|os|ue|uen|amos)|tarjeta|link de pago|niubiz|visa|mastercard|deposit\w*|transferen\w*|yape|plin|inscrib\w*|inscripcion|matricul\w*|medios? de pago|formas? de pago')) {
            $curso = $esteCurso ? $this->cursoPagina : ($busqueda['cursos'][0] ?? $previos->first());
            $botones = [];
            if ($curso?->link_pago) {
                $botones[] = $this->enlace('Pagar ' . ($curso->numero_texto ?: 'este curso') . ' con tarjeta', $curso->link_pago, 'bx-credit-card', true);
            }
            $botones[] = $this->enlace('Registrar mi pago', route('pedido.create'), 'bx-upload');
            $botones[] = $this->whatsapp('Quisiera ayuda para comprar un curso.', 'Ayuda para pagar');

            return $this->r(
                "Comprar es fácil, en 3 pasos:\n\n**1.** Entra al curso y pulsa **Comprar ahora**: pagas con tarjeta (Visa, Mastercard…) en su link de pago seguro.\n**2.** Registra tu pago y adjunta la constancia.\n**3.** Recibe los enlaces de descarga en tu correo.\n\nSi pagas desde fuera del Perú o prefieres otro medio, escríbenos por WhatsApp.",
                $curso ? [$curso] : [],
                $botones,
            );
        }

        // 5) Certificado
        if ($this->tiene('certificad\w*|diploma|constancia de (participacion|estudios)|certifica')) {
            return $this->r(
                'Sí. Todos los cursos incluyen un **certificado digital de participación** emitido por ' . config('cursos.nombre') . ". Te lo enviamos junto con los enlaces de descarga.",
                botones: [$this->pregunta('¿Qué incluye cada curso?'), $this->pregunta('¿Cómo compro un curso?')],
            );
        }

        // 6) Empresas y facturas
        if ($this->tiene('empresa\w*|corporativ\w*|factura\w*|ruc|varios participantes|grupo de|in house|personal de mi')) {
            $correo = config('cursos.correo_informes') ?: config('cursos.correo_pedidos');

            return $this->r(
                "Para empresas tenemos **precios especiales** según el número de participantes, y emitimos factura.\n\nEscríbenos a **{$correo}** o por WhatsApp con los cursos que te interesan y cuántas personas son.",
                botones: [$this->whatsapp('Quisiera una cotización para empresa.', 'Cotizar para mi empresa'), $this->enlace('Escribir un correo', 'mailto:' . $correo, 'bx-envelope')],
            );
        }

        // 7) Qué incluye / formato (de un curso concreto o en general)
        if ($this->tiene('incluye|que trae|formato|mp4|en vivo|vivo|grabad\w*|duracion|cuanto dura|horas|temario|contenido|caduca|vence|expira|de por vida|para siempre')) {
            $curso = $esteCurso ? $this->cursoPagina : ($busqueda['cursos'][0] ?? $previos->first());
            if ($curso) {
                return $this->detalleCurso($curso);
            }
            $incluye = implode("\n", array_map(fn ($l) => '• ' . $l, \App\Models\Ajuste::lineas(config('cursos.incluye'))));
            if ($this->tiene('en vivo|vivo|zoom|grabad\w*|caduca|vence|expira|de por vida|para siempre')) {
                return $this->r(
                    "No son en vivo: son **grabaciones completas** de cursos y congresos que InterMet ya realizó con expertos internacionales.\n\nTe los descargas en MP4 con el material de trabajo, **son tuyos y no vencen**: los ves cuando quieras y las veces que quieras.",
                    botones: [$this->pregunta('Buscar un curso'), $this->pregunta('¿Cómo compro un curso?')],
                );
            }

            return $this->r(
                "Cada curso incluye:\n{$incluye}\n\nSon cursos que InterMet ya realizó con expertos internacionales: los **descargas y son tuyos**, sin fecha de vencimiento.",
                botones: [$this->pregunta('Buscar un curso'), $this->pregunta('¿Cómo compro un curso?')],
            );
        }

        // Ofertas y descuentos
        if ($this->tiene('descuento\w*|oferta\w*|promo\w*|rebaja\w*|cupon\w*') && ! $hay) {
            $ofertas = Curso::publicados()->where('en_oferta', true)->whereNotNull('precio_oferta')->orderByDesc('anio')->limit(3)->get();

            return $ofertas->isNotEmpty()
                ? $this->r('¡Sí! Estos cursos están **en oferta** ahora mismo:', $ofertas, [$this->whatsapp('Quisiera saber si hay descuentos por varios cursos.', 'Descuento por varios cursos')])
                : $this->r(
                    "Por ahora no hay ofertas publicadas en la web. Pero si compras **varios cursos** o eres una **empresa**, escríbenos y te preparamos un precio especial.",
                    botones: [$this->whatsapp('Quisiera saber si hay descuento por varios cursos.', 'Pedir un descuento'), $this->pregunta('Buscar un curso')],
                );
        }

        // 8) Precios
        $preguntaPrecio = $this->tiene('precio\w*|cuest(a|an)|cuanto (es|vale|valen|sale|salen|cobran)|costo\w*|tarifa\w*|soles|dolares|usd|descuento\w*|oferta\w*|promo\w*|barato\w*|economic\w*');
        if ($preguntaPrecio && ! $hay) {
            $curso = $esteCurso ? $this->cursoPagina : $previos->first();
            if ($curso) {
                return $this->detalleCurso($curso);
            }
            $min = Curso::publicados()->min('precio');
            $max = Curso::publicados()->max('precio');
            $baratos = Curso::publicados()->orderBy('precio')->orderByDesc('anio')->limit(3)->get();

            return $this->r(
                'Los precios van desde **' . $this->usd($min) . '** hasta ' . $this->usd($max) . ' según el curso (los congresos completos son los de mayor precio). Están en dólares y pagas una sola vez.' .
                "\n\nDime el tema que te interesa y te digo el precio exacto. Estos son algunos de los más accesibles:",
                $baratos,
                [$this->pregunta('Buscar un curso'), $this->whatsapp('Quisiera saber si hay descuentos.', 'Preguntar por descuentos')],
                ['texto' => 'Ver todos de menor a mayor precio', 'url' => route('cursos.index', ['orden' => 'precio_menor']) . '#catalogo'],
            );
        }

        // 9) Encontró cursos con lo que escribió
        if ($hay) {
            $n = $busqueda['total'];
            $sobre = $busqueda['etiqueta'];
            $intro = $n === 1
                ? "Encontré **1 curso** {$sobre}:"
                : "Encontré **{$n} cursos** {$sobre}" . ($n > 3 ? '. Estos son los más recientes:' : ':');
            if ($preguntaPrecio) {
                $intro .= "\nEl precio de cada uno está en la tarjeta (en dólares, pago único).";
            }

            return $this->r(
                $intro,
                $busqueda['cursos'],
                [$this->pregunta('¿Cómo compro un curso?'), $this->pregunta('¿Qué incluye cada curso?')],
                $n > 3 ? ['texto' => 'Ver más en el catálogo', 'url' => $busqueda['url']] : null,
            );
        }

        // 10) Catálogo por temas
        if ($this->tiene('buscar un curso|buscar otro curso|otro curso|que cursos|cursos (tienen|hay|ofrecen)|catalogo|categorias|lista de cursos|que ofrecen|que temas|todos los cursos|recomiend\w*|sugier\w*|recomendacion|principiante|empezar')) {
            return $this->temas();
        }

        // 11) Nuevos, en inglés, congresos (sin otro tema)
        if ($busqueda['filtro'] !== null) {
            return $this->r('No encontré cursos con esa combinación. Prueba con otro tema:', botones: $this->botonesTemas());
        }

        // 13) Saludos y cortesía
        if ($palabras <= 5 && $this->tiene('hola|buenas|buenos dias|buenas tardes|buenas noches|hey|holi|saludos|que tal|hello|hi')) {
            return $this->r('¡Hola! 👋 ¿En qué te ayudo? Puedo buscarte un curso por tema o instructor, decirte el precio o explicarte cómo comprar.', botones: $this->botonesInicio());
        }
        if ($palabras <= 6 && $this->tiene('gracias|thanks|thank you|perfecto|genial|excelente|listo|vale|entendido|ok')) {
            return $this->r('¡Con gusto! Si necesitas algo más, aquí estoy. 😊', botones: [$this->pregunta('Buscar un curso'), $this->whatsapp(null, 'Hablar con un asesor')]);
        }
        if ($palabras <= 5 && $this->tiene('chau|chao|adios|hasta luego|nos vemos|bye')) {
            return $this->r('¡Hasta pronto! Que tengas un buen día.');
        }

        // 14) No entendió
        return $this->r(
            "Disculpa, con eso no puedo ayudarte. 🙂 Solo atiendo consultas sobre los **cursos y congresos digitales de minería** de " . config('cursos.nombre') . ".\n\nSi buscas un curso, escríbeme el **tema** (por ejemplo «flotación» o «relaves»), el **nombre del instructor** o el **N° del curso**. O te atiende una persona por WhatsApp.",
            botones: array_merge([$this->pregunta('Buscar un curso')], [$this->whatsapp(null, 'Hablar con un asesor')]),
        );
    }

    private function fueraDeTema(array $ajenas): array
    {
        $this->fueraDeTema = true;
        $que = $this->escrito[$ajenas[0]] ?? $ajenas[0];

        return $this->r(
            "Disculpa, sobre «{$que}» no puedo ayudarte. 🙂\n\nSoy el asistente de **" . config('cursos.nombre') . "** y solo atiendo consultas sobre nuestros **cursos y congresos digitales de minería**: flotación, molienda, hidrometalurgia, oro y cobre, relaves, muestreo, simulación y más.\n\n¿Te ayudo a encontrar un curso?",
            botones: array_merge(array_slice($this->botonesTemas(), 0, 6), [$this->whatsapp(null, 'Hablar con un asesor')]),
            verMas: ['texto' => 'Ver el catálogo completo', 'url' => route('cursos.index') . '#catalogo'],
        );
    }

    /**
     * Palabras de la pregunta que no aparecen en ningún curso ni son de la compra/atención.
     * Solo cuenta si TODAS las palabras importantes son ajenas (así «curso de flotación para mi empresa» no entra).
     */
    private function palabrasAjenas(): array
    {
        $claves = $this->clavesDe($this->texto);
        if (! $claves) {
            return [];
        }
        $vocabulario = $this->vocabulario();
        $ajenas = array_values(array_filter($claves, function ($p) use ($vocabulario) {
            if (mb_strlen($p) < 4 || isset(self::SINONIMOS[$p]) || in_array($p, self::ATENCION, true)) {
                return false;
            }
            foreach ($this->variantes($p) as $v) {
                if (str_contains($vocabulario, ' ' . trim($v))) {
                    return false;
                }
            }

            return true;
        }));

        return count($ajenas) === count(array_filter($claves, fn ($p) => mb_strlen($p) >= 4)) ? $ajenas : [];
    }

    /** Todas las palabras que salen en los cursos y en los textos de la web (sin tildes, en minúscula) */
    private function vocabulario(): string
    {
        $armar = function () {
            $texto = Curso::publicados()->get(['titulo', 'instructor', 'incluye', 'resumen', 'temario', 'dirigido_a'])
                ->map(fn ($c) => implode(' ', [$c->titulo, $c->instructor, $c->incluye, $c->resumen, $c->temario, $c->dirigido_a]))
                ->push(config('cursos.descripcion'), config('cursos.incluye'), config('cursos.dirigido_a'), config('cursos.envio'))
                ->implode(' ');
            $palabras = array_unique(explode(' ', preg_replace('/[^a-z0-9ñ]+/', ' ', Str::lower(Str::ascii($texto)))));

            return ' ' . implode(' ', $palabras) . ' ';
        };

        try {
            return Cache::remember('chatbot:vocabulario', 600, $armar);
        } catch (Throwable) {
            return $armar();
        }
    }

    private function contacto(): array
    {
        $correo = config('cursos.correo_pedidos');

        return $this->r(
            "Claro, te atiende una persona del equipo:\n\n• WhatsApp: **" . config('cursos.telefono') . "**\n• Correo: **{$correo}**\n• Horario: " . config('cursos.horario'),
            botones: [$this->whatsapp(null, 'Escribir por WhatsApp'), $this->enlace('Enviar un correo', 'mailto:' . $correo, 'bx-envelope')],
        );
    }

    private function temas(): array
    {
        return $this->r(
            'Tenemos **' . Curso::publicados()->where('tipo', 'curso')->count() . ' cursos** y **' . Curso::publicados()->where('tipo', 'congreso')->count() . " congresos** grabados. ¿Qué tema te interesa? También puedes escribirme el nombre de un instructor.",
            botones: $this->botonesTemas(),
            verMas: ['texto' => 'Ver el catálogo completo', 'url' => route('cursos.index') . '#catalogo'],
        );
    }

    private function detalleCurso(Curso $curso): array
    {
        $partes = ['**' . Str::limit($curso->titulo, 110) . '**'];
        $partes[] = '• Precio: **' . $this->usd($curso->precio_actual) . '**' . ($curso->en_oferta && $curso->precio_oferta ? ' (antes ' . $this->usd($curso->precio) . ')' : '') . ', pago único.';
        if ($curso->horas) {
            $partes[] = '• Duración: ' . rtrim(rtrim(number_format((float) $curso->horas, 1), '0'), '.') . ' horas de video.';
        }
        if ($curso->instructor) {
            $partes[] = '• Dicta: ' . $curso->instructor . '.';
        }
        $partes[] = '• Idioma: ' . (Curso::IDIOMAS[$curso->idioma] ?? $curso->idioma) . ($curso->anio ? ' · Grabado en ' . $curso->anio : '') . '.';
        $incluye = array_merge(\App\Models\Ajuste::lineas(config('cursos.incluye')), $curso->incluye ? [trim($curso->incluye)] : []);
        $partes[] = '• Incluye: ' . Str::lower(implode(', ', array_unique($incluye))) . '.';

        $botones = [];
        if ($curso->link_pago) {
            $botones[] = $this->enlace('Comprar con tarjeta', $curso->link_pago, 'bx-credit-card', true);
        }
        $botones[] = $this->pregunta('¿Cómo compro un curso?');
        $botones[] = $this->whatsapp('Tengo una consulta sobre el curso ' . trim($curso->numero_texto . ' ' . Str::limit($curso->titulo, 60)) . '.', 'Preguntar por este curso');

        return $this->r(implode("\n", $partes), [$curso], $botones);
    }

    /* =====================================================================
       Búsqueda en el catálogo
       ===================================================================== */

    /** @return array{cursos: Collection, total: int, etiqueta: string, url: string, filtro: ?string} */
    private function buscar(): array
    {
        $t = $this->texto;
        $idioma = $this->tiene('ingles|english|in english') ? 'en' : null;
        $tipo = $this->tiene('congreso\w*|conferencia\w*|encuentro\w*|conference\w*') ? 'congreso' : null;
        $anio = preg_match('/\b(20[0-3]\d)\b/', $t, $m) ? (int) $m[1] : null;
        $nuevos = $this->tiene('nuev[oa]s?|reciente\w*|ultim[oa]s?|novedad\w*|lo ultimo');
        $numero = preg_match('/(?:#\s*|\bn(?:ro|umero|°)?\s*|\bcurso\s+)(\d{1,3})\b/', $t, $m) ? (int) $m[1] : null;

        // Palabras clave (sin las que no sirven para buscar ni las de la pregunta)
        $claves = $this->clavesDe($t);

        $filtro = $idioma || $tipo || $anio || $nuevos ? 'si' : null;
        if (! $claves && ! $numero && ! $filtro) {
            return ['cursos' => collect(), 'total' => 0, 'etiqueta' => '', 'url' => '', 'filtro' => null];
        }

        $cursos = Curso::publicados()
            ->when($idioma, fn ($q) => $q->where('idioma', $idioma))
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->when($anio, fn ($q) => $q->where('anio', $anio))
            ->get();

        if ($numero) {
            $exacto = $cursos->where('numero', $numero)->sortBy(fn ($c) => $c->idioma === 'es' ? 0 : 1)->values();
            if ($exacto->isNotEmpty()) {
                return ['cursos' => $exacto->take(3), 'total' => $exacto->count(), 'etiqueta' => 'con el N° ' . $numero, 'url' => route('cursos.index', ['q' => $numero]) . '#catalogo', 'filtro' => null];
            }
        }

        if ($claves) {
            $puntuados = $cursos->map(function (Curso $c) use ($claves) {
                $titulo = ' ' . Str::lower(Str::ascii($c->titulo)) . ' ';
                $persona = ' ' . Str::lower(Str::ascii((string) $c->instructor)) . ' ';
                $resto = ' ' . Str::lower(Str::ascii($c->incluye . ' ' . $c->resumen . ' ' . $c->temario)) . ' ';
                $puntos = 0;
                $aciertos = 0;
                $enTitulo = false;
                foreach ($claves as $clave) {
                    $p = 0;
                    foreach ($this->variantes($clave) as $v) {
                        $re = '/\b' . preg_quote($v, '/') . '/';
                        $p = max($p, preg_match($re, $titulo) ? 4 : (preg_match($re, $persona) ? 3 : (preg_match($re, $resto) ? 1 : 0)));
                    }
                    $puntos += $p;
                    $aciertos += $p > 0 ? 1 : 0;
                    $enTitulo = $enTitulo || $p >= 3;
                }

                return ['curso' => $c, 'puntos' => $puntos, 'aciertos' => $aciertos, 'titulo' => $enTitulo];
            })->filter(fn ($x) => $x['titulo']);

            $max = $puntuados->max('aciertos');
            $mejores = $puntuados->where('aciertos', $max)
                ->sortBy([['puntos', 'desc'], [fn ($a, $b) => ($b['curso']->anio ?? 0) <=> ($a['curso']->anio ?? 0)]])
                ->pluck('curso')->values();
        } else {
            if ($nuevos && ! $anio) {
                $ultimo = (int) $cursos->max('anio');
                $cursos = $cursos->filter(fn ($c) => $c->anio >= $ultimo - 1);
            }
            $mejores = $cursos->sortByDesc(fn ($c) => sprintf('%04d%03d', $c->anio ?? 0, $c->numero ?? 0))->values();
        }

        // El mismo curso en español e inglés (mismo N°): se muestra una sola vez
        $mejores = $mejores->sortBy(fn ($c) => $c->idioma === ($idioma ?? 'es') ? 0 : 1)
            ->unique(fn ($c) => $c->numero ? 'n' . $c->numero : 'id' . $c->id)
            ->sortBy(fn ($c) => $mejores->search($c))->values();

        $etiqueta = collect([
            $claves ? 'sobre «' . Str::limit(implode(' ', array_map(fn ($c) => $c === 'ia' ? 'IA' : ($this->escrito[$c] ?? $c), $claves)), 40) . '»' : ($nuevos ? 'recientes' : null),
            $tipo ? '(congresos)' : null,
            $idioma ? 'en inglés' : null,
            $anio ? "grabados en {$anio}" : null,
        ])->filter()->implode(' ');

        $q = $claves ? implode(' ', array_slice($claves, 0, 2)) : null;

        return [
            'cursos' => $mejores->take(3),
            'total' => $mejores->count(),
            'etiqueta' => $etiqueta,
            'url' => route('cursos.index', array_filter(['q' => $q, 'idioma' => $idioma, 'tipo' => $tipo, 'anio' => $anio, 'orden' => $q ? null : 'recientes'])) . '#catalogo',
            'filtro' => $filtro,
        ];
    }

    private function clavesDe(string $t): array
    {
        $limpio = preg_replace('/\b(20[0-3]\d|\d{1,3})\b/', ' ', $t);
        $claves = array_values(array_unique(array_filter(
            explode(' ', $limpio),
            fn ($p) => mb_strlen($p) >= 3 && ! in_array($p, self::VACIAS, true) && ! preg_match('/^(precio|cuest|cost|vale|compr|pag|cuant|incluy|certif|descarg|envi|recib|dura|hora|temari|ofert|descuent|dolar|soles|usd|tarjet|link|horario|formato|grabad|vivo|contenido|interes|encontr|mostr|ensen|enseñ|consult|vend|ofrec|adquir)/', $p)
        )));
        if ($this->tiene('ia')) {
            $claves[] = 'ia';
        }

        return array_slice($claves, 0, 6);
    }

    /** Formas en que puede aparecer una palabra: raíz + sinónimos */
    private function variantes(string $p): array
    {
        $lista = self::SINONIMOS[$p] ?? [];
        $largo = mb_strlen($p);
        $raiz = $largo >= 7 ? mb_substr($p, 0, $largo - 2) : ($largo >= 5 ? preg_replace('/(es|s)$/', '', $p) : $p);
        $lista[] = $raiz;

        return array_unique($lista);
    }

    /* =====================================================================
       Con IA (Claude): redacta la respuesta con los mismos datos
       ===================================================================== */

    private function conIa(string $mensaje, array $contexto, array $base): array
    {
        $candidatos = collect($base['cursos'])->map(fn ($c) => $c['id']);
        $busqueda = $this->buscar();
        $lista = Curso::publicados()->whereIn('id', $candidatos->merge($busqueda['cursos']->pluck('id'))->unique())->get();
        if ($this->cursoPagina) {
            $lista->prepend($this->cursoPagina);
        }
        if ($lista->isEmpty()) {
            $lista = Curso::publicados()->orderByDesc('anio')->orderByDesc('numero')->limit(8)->get();
        }
        $lista = $lista->unique('id')->take(8);

        $cursosTexto = $lista->map(fn (Curso $c) => sprintf(
            '- %s | %s | %s | %s | %s | %s',
            $c->numero_texto ?: '(sin N°)', Str::limit($c->titulo, 120), $this->usd($c->precio_actual),
            Curso::IDIOMAS[$c->idioma] ?? $c->idioma, $c->anio ?: 's/f', $c->instructor ?: 'varios expertos'
        ))->implode("\n");

        $sistema = implode("\n", [
            'Eres «' . config('cursos.chatbot_nombre', 'Asistente InterMet') . '», el asistente de la web de cursos digitales de ' . config('cursos.nombre') . '.',
            'Hablas en español neutro y peruano, de «tú», amable y breve: máximo 4 oraciones. Si te escriben en inglés, responde en inglés.',
            'Usa **negritas** solo para lo importante. No uses títulos, tablas ni enlaces: los botones y tarjetas los pone la web.',
            'Nunca inventes cursos, precios, fechas, descuentos ni datos de contacto. Si no sabes algo, sugiere escribir por WhatsApp.',
            'Para recomendar un curso menciona su número con # (por ejemplo #82): la web mostrará su tarjeta. Solo cursos de la lista de abajo.',
            'Solo hablas de los cursos y congresos digitales de minería de InterMet y de cómo comprarlos. Si preguntan por otra cosa (productos como herramientas o medicinas, otros temas, tareas, noticias, etc.), di con amabilidad que solo atiendes consultas sobre estos cursos de minería y ofrece buscar un curso.',
            '',
            'DATOS DE LA TIENDA',
            '- Qué son: ' . config('cursos.descripcion'),
            '- Cada curso incluye: ' . str_replace("\n", ', ', (string) config('cursos.incluye')) . '. Se descargan y no vencen. No son en vivo.',
            '- Cómo comprar: 1) en la página del curso, botón «Comprar ahora», se paga con tarjeta en su link de pago (Niubiz); 2) «Registrar pago» con la constancia; 3) llegan los enlaces de descarga al correo.',
            '- Envío: ' . config('cursos.envio'),
            '- Precios en dólares (USD), pago único. Precios para empresas: consultar a ' . (config('cursos.correo_informes') ?: config('cursos.correo_pedidos')) . '.',
            '- Contacto: WhatsApp ' . config('cursos.telefono') . ', correo ' . config('cursos.correo_pedidos') . ', horario ' . config('cursos.horario') . '.',
            '- Catálogo: ' . Curso::publicados()->count() . ' cursos y congresos grabados.',
            '',
            $this->cursoPagina ? 'El visitante está viendo la página del curso ' . ($this->cursoPagina->numero_texto ?: '') . ' «' . $this->cursoPagina->titulo . '».' : '',
            'CURSOS RELACIONADOS CON LA PREGUNTA (N° | título | precio | idioma | año | instructor):',
            $cursosTexto,
            '',
            'Respuesta automática que daría la web (úsala como guía de los datos): ' . $base['texto'],
        ]);

        $mensajes = [];
        foreach (array_slice((array) ($contexto['historial'] ?? []), -6) as $h) {
            $rol = ($h['rol'] ?? '') === 'bot' ? 'assistant' : 'user';
            $txt = Str::limit(trim((string) ($h['texto'] ?? '')), 600);
            if ($txt === '' || (! $mensajes && $rol === 'assistant')) {
                continue;
            }
            if ($mensajes && end($mensajes)['role'] === $rol) {
                $mensajes[count($mensajes) - 1]['content'] .= "\n" . $txt;
            } else {
                $mensajes[] = ['role' => $rol, 'content' => $txt];
            }
        }
        if ($mensajes && end($mensajes)['role'] === 'user') {
            array_pop($mensajes);
        }
        $mensajes[] = ['role' => 'user', 'content' => Str::limit($mensaje, 500)];

        $r = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.modelo'),
            'max_tokens' => 400,
            'system' => $sistema,
            'messages' => $mensajes,
        ])->throw()->json();

        $texto = trim(collect($r['content'] ?? [])->where('type', 'text')->pluck('text')->implode("\n"));
        if ($texto === '') {
            return $base;
        }

        // Tarjetas: los cursos que la IA mencionó con #N
        preg_match_all('/#(\d{1,3})\b/', $texto, $m);
        $mencionados = collect($m[1])->map(fn ($n) => (int) $n)->unique()
            ->map(fn ($n) => $lista->first(fn ($c) => (int) $c->numero === $n))->filter()->take(3);

        return [
            'texto' => $texto,
            'cursos' => $mencionados->isNotEmpty() ? $this->tarjetas($mencionados) : $base['cursos'],
            'botones' => $base['botones'],
            'ver_mas' => $base['ver_mas'],
            'ia' => true,
        ];
    }

    /* =====================================================================
       Ayudantes
       ===================================================================== */

    private function tiene(string $patron): bool
    {
        return (bool) preg_match('/\b(?:' . $patron . ')\b/u', $this->texto);
    }

    private function usd(mixed $m): string
    {
        return 'USD ' . (fmod((float) $m, 1) ? number_format((float) $m, 2) : number_format((float) $m));
    }

    private function r(string $texto, iterable $cursos = [], array $botones = [], ?array $verMas = null): array
    {
        return ['texto' => $texto, 'cursos' => $this->tarjetas(collect($cursos)), 'botones' => array_values($botones), 'ver_mas' => $verMas];
    }

    private function tarjetas(Collection $cursos): array
    {
        return $cursos->map(function (Curso $c) {
            [$tema, $color, $icono] = $c->tema;

            return [
                'id' => $c->id,
                'numero' => $c->numero_texto,
                'titulo' => Str::limit($c->titulo, 95),
                'precio' => $this->usd($c->precio_actual),
                'antes' => $c->en_oferta && $c->precio_oferta ? $this->usd($c->precio) : null,
                'meta' => trim(($c->idioma === 'en' ? 'English' : 'Español') . ($c->anio ? ' · ' . $c->anio : '') . ($c->tipo === 'congreso' ? ' · Congreso' : '')),
                'url' => $c->url,
                'imagen' => $c->imagen_url,
                'tema' => $tema,
                'color' => $color,
                'icono' => $icono,
            ];
        })->values()->all();
    }

    private function botonesInicio(): array
    {
        return [
            $this->pregunta('Buscar un curso', 'bx-search'),
            $this->pregunta('¿Cómo compro un curso?', 'bx-cart-alt'),
            $this->pregunta('¿Cuándo recibo mi curso?', 'bx-download'),
            $this->pregunta('Cursos nuevos', 'bx-star'),
            $this->whatsapp(null, 'Hablar con un asesor'),
        ];
    }

    private function botonesTemas(): array
    {
        return array_map(fn ($t) => ['tipo' => 'pregunta', 'texto' => $t[0], 'enviar' => 'Cursos de ' . $t[1]], self::TEMAS);
    }

    private function pregunta(string $texto, ?string $icono = null): array
    {
        return ['tipo' => 'pregunta', 'texto' => $texto, 'enviar' => $texto, 'icono' => $icono];
    }

    private function enlace(string $texto, string $url, string $icono, bool $nueva = false): array
    {
        return ['tipo' => 'enlace', 'texto' => $texto, 'url' => $url, 'icono' => $icono, 'nueva' => $nueva];
    }

    private function whatsapp(?string $texto = null, string $etiqueta = 'Escribir por WhatsApp'): array
    {
        $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
        $msg = rawurlencode('Hola InterMet, ' . lcfirst($texto ?? 'tengo una consulta sobre los cursos digitales.'));

        return ['tipo' => 'whatsapp', 'texto' => $etiqueta, 'url' => "https://wa.me/{$wa}?text={$msg}", 'icono' => 'bxl-whatsapp', 'nueva' => true];
    }

    /** Lo que se muestra al abrir el chat */
    public function bienvenida(?string $slug = null): array
    {
        $curso = $slug ? Curso::publicados()->where('slug', $slug)->first() : null;
        $texto = (string) config('cursos.chatbot_bienvenida');
        if ($curso) {
            return $this->r($texto . "\n\nVeo que estás mirando **" . Str::limit($curso->titulo, 80) . '**. ¿Quieres saber algo de este curso?', botones: [
                $this->pregunta('¿Qué incluye este curso?', 'bx-list-check'),
                $this->pregunta('¿Cómo compro este curso?', 'bx-cart-alt'),
                $this->pregunta('Buscar otro curso', 'bx-search'),
                $this->whatsapp('Tengo una consulta sobre el curso ' . trim($curso->numero_texto . ' ' . Str::limit($curso->titulo, 60)) . '.', 'Hablar con un asesor'),
            ]);
        }

        return $this->r($texto, botones: $this->botonesInicio());
    }
}
