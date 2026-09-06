<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Comprobación rápida de la configuración de correo:
 *   php artisan mail:test tu@correo.com
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Destinatario de la prueba}';

    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    public function handle(): int
    {
        $email = $this->argument('email');
        $mailer = config('mail.default');

        $this->info("Enviando con el mailer «{$mailer}» a {$email}…");

        try {
            Mail::raw(
                'Correo de prueba de '.config('app.name').'. Si lo recibes, la configuración funciona.',
                fn ($m) => $m->to($email)->subject('Prueba de correo · '.config('app.name'))
            );
        } catch (\Throwable $e) {
            $this->error('Error al enviar: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log: el correo se ha escrito en storage/logs/laravel.log, no se ha enviado.');
        } else {
            $this->info('Enviado sin errores. Revisa la bandeja de entrada (y spam).');
        }

        return self::SUCCESS;
    }
}
