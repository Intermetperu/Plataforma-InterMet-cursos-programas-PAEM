<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Envía un correo sin romper el proceso si el servidor de correo no está configurado. */
class Correo
{
    public static function enviar(?string $para, Mailable $correo): bool
    {
        if (! $para || ! filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            Mail::to($para)->send($correo);

            return true;
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el correo a ' . $para . ': ' . $e->getMessage());

            return false;
        }
    }

    /** ¿Los correos salen de verdad? (con MAIL_MAILER=log solo se guardan en storage/logs) */
    public static function configurado(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null], true);
    }
}
