<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoPedidoAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuevo pago por verificar · ' . $this->pedido->codigo . ' · ' . $this->pedido->nombre . '');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.nuevo-pedido');
    }
}
