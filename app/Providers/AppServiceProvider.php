<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // La app maneja datos de salud de menores: fuera de local, todo por HTTPS.
        // Se fuerza también si APP_URL ya es https, sin depender de que APP_ENV
        // esté bien puesto en el entorno de despliegue.
        if (! $this->app->environment('local')
            || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Correos de autenticación en español (Laravel los genera en inglés).
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Acceso a la plataforma del grupo scout')
                ->greeting('¡Hola!')
                ->line('Has recibido este correo para que establezcas la contraseña de tu cuenta.')
                ->action('Establecer contraseña', $url)
                ->line("Este enlace caduca en {$minutes} minutos.")
                ->line('Si no esperabas este correo, puedes ignorarlo.')
                ->salutation('Un saludo, '.config('app.name'));
        });

        // Registro del último inicio de sesión (pantalla de gestión de usuarios).
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo electrónico')
                ->greeting('¡Hola!')
                ->line('Confirma tu dirección de correo para activar tu cuenta.')
                ->action('Verificar correo', $url)
                ->line('Si no has creado ninguna cuenta, no hace falta que hagas nada.')
                ->salutation('Un saludo, '.config('app.name'));
        });
    }
}
