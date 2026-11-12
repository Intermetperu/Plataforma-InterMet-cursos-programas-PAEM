<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'codigo', 'token', 'estado', 'nombre', 'email', 'celular', 'pais', 'documento', 'empresa', 'nota', 'total',
    'pago_metodo', 'pago_moneda', 'pago_monto', 'pago_fecha', 'pago_operacion', 'pago_operacion_norm', 'pago_captura',
    'pago_captura_hash', 'pago_captura_huella', 'pago_alertas', 'motivo_rechazo', 'enlace_descarga', 'mensaje_cliente',
    'atendido_por', 'verificado_en', 'enviado_en', 'ip',
])]
class Pedido extends Model
{
    public const ESTADOS = [
        'por_verificar' => ['Por verificar', 'warning', 'Revisando su pago'],
        'pagado' => ['Pago verificado', 'info', 'Pago verificado · preparando sus enlaces'],
        'enviado' => ['Acceso enviado', 'success', 'Enlaces de descarga enviados a su correo'],
        'rechazado' => ['Rechazado', 'danger', 'No pudimos verificar el pago'],
    ];

    /** Medios de pago: [nombre, ¿se paga en soles por defecto?] */
    public const METODOS = [
        'niubiz' => ['Tarjeta (link de pago Niubiz)', 'USD'],
        'bcp' => ['Transferencia o depósito BCP', 'PEN'],
        'interbank' => ['Transferencia o depósito Interbank', 'PEN'],
        'bbva' => ['Transferencia BBVA', 'PEN'],
        'scotiabank' => ['Transferencia Scotiabank', 'PEN'],
        'yape' => ['Yape', 'PEN'],
        'plin' => ['Plin', 'PEN'],
        'moneygram' => ['MoneyGram / Western Union (fuera del Perú)', 'USD'],
        'otro' => ['Otro', 'USD'],
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'pago_monto' => 'decimal:2',
            'pago_fecha' => 'date',
            'pago_alertas' => 'array',
            'verificado_en' => 'datetime',
            'enviado_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Pedido $p) {
            $p->token ??= Str::random(40);
            $p->codigo ??= 'TMP-' . Str::random(8);
        });
        // Código correlativo legible: C-000001
        static::created(function (Pedido $p) {
            if (str_starts_with($p->codigo, 'TMP-')) {
                $p->forceFill(['codigo' => 'C-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** «0012-3456, 789012 y 345678» → ['00123456', '789012', '345678'] */
    public static function operaciones(?string $texto): array
    {
        return collect(preg_split('/[,;\/\n]+|\s+y\s+/iu', (string) $texto))
            ->map(fn ($o) => \App\Support\RevisionCaptura::normalizarOperacion($o))
            ->filter(fn ($o) => strlen($o) >= 4)->unique()->values()->all();
    }

    /** Cómo se guarda para buscar repetidos: «,00123456,789012,» */
    public static function operacionesNorm(?string $texto): string
    {
        $ops = static::operaciones($texto);

        return $ops ? ',' . implode(',', $ops) . ',' : '';
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoCurso::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'secondary';
    }

    public function getMetodoTextoAttribute(): string
    {
        return self::METODOS[$this->pago_metodo][0] ?? $this->pago_metodo;
    }

    /** Monto pagado expresado en USD (si pagó en soles, con el tipo de cambio de la web) */
    public function getPagoEnUsdAttribute(): float
    {
        $tc = (float) config('cursos.tipo_cambio') ?: 1;

        return $this->pago_moneda === 'PEN' ? round((float) $this->pago_monto / $tc, 2) : (float) $this->pago_monto;
    }

    public function getEnlacesAttribute(): array
    {
        return Ajuste::lineas($this->enlace_descarga);
    }

    public function getUrlEstadoAttribute(): string
    {
        return route('pedido.estado', $this->token);
    }
}
