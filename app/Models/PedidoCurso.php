<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pedido_id', 'curso_id', 'programa_id', 'numero', 'titulo', 'precio'])]
class PedidoCurso extends Model
{
    protected $table = 'pedido_curso';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['precio' => 'decimal:2'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
