# CLAUDE.md — Guía para trabajar en este repositorio

Plataforma de gestión de un grupo scout (MSC Andalucía). Laravel 11 · Inertia · Vue 3 · Tailwind ·
spatie/permission + activitylog. UI en español; código y base de datos en inglés.

Lee también **[CONVENTIONS.md](CONVENTIONS.md)** (contrato de estilo y arquitectura),
**[CHANGES.md](CHANGES.md)** (registro de migraciones aditivas y cambios al núcleo — añade una
fila cada vez que toques el esquema o modelos compartidos) y
**[docs/ESTADO.md](docs/ESTADO.md)** (mapa vivo de lo que ya existe: módulos, modelos, rutas,
roles y qué está completo / parcial / pendiente) junto con **[docs/ROADMAP.md](docs/ROADMAP.md)**
(plan por fases, priorizado por ahorro de tiempo a los responsables).

## Mantener el contexto al día (OBLIGATORIO en cada cambio funcional)

Cada vez que termines algo nuevo — una feature, una pantalla, un flujo, un comando, un cambio de
permisos o de workflow — **antes de cerrar el trabajo**:

1. **`docs/ESTADO.md`**: mueve la línea correspondiente de "Parcial/Pendiente" a "Hecho" (o añádela),
   con una frase de qué hace y dónde vive (controlador / página / ruta principal). Este documento es
   la foto actual; mantenlo verdadero.
2. **`CHANGES.md`**: añade fila solo si tocaste esquema o modelos compartidos (ya era la regla).
3. **`docs/ROADMAP.md`**: si completaste un punto del plan, márcalo `[x]`; si el trabajo cambió la
   prioridad de lo que queda, reordena.
4. Si añadiste una convención nueva o un componente compartido, anótalo en `CONVENTIONS.md`.

Así la siguiente sesión arranca con el contexto sin tener que reanalizar el repo.

## Idioma — la web es 100 % en español

- **Todo texto visible para la persona usuaria va en español.** Sin excepción: páginas, botones,
  etiquetas de formulario, `placeholder`, títulos `<Head>`, `<title>`, mensajes de error, toasts,
  correos, PDF y validación.
- **Código en inglés**: nombres de clases, variables, rutas, columnas, claves de traducción.
  Comentarios en español permitidos.
- Nada de dejar strings del scaffolding de Breeze/Jetstream en inglés. Si añades o regeneras una
  página de auth/perfil, tradúcela en el mismo commit.
- Traducciones de servidor en `lang/es/*.php`. En los componentes Vue el proyecto usa texto en
  español directamente en la plantilla (patrón ya establecido); mantenlo así salvo que reutilices
  una clave de `lang/es`.
- `APP_LOCALE=es`, `APP_FALLBACK_LOCALE=es`, `APP_FAKER_LOCALE=es_ES` en `.env`
  (el `.env.example` viene en `en`; ponlo en `es` en cualquier entorno nuevo).
- Los correos de Laravel (reset de contraseña, verificación) se generan en inglés: se
  sobreescriben en español en `AppServiceProvider::boot()` con `ResetPassword::toMailUsing()` /
  `VerifyEmail::toMailUsing()`. Si añades otra notificación del framework, hazla en español ahí.
- Envío de correo: SMTP de Gmail (Workspace del grupo, `smtp.gmail.com:587`, contraseña de
  aplicación). Se usa para recordatorios de pago, avisos de caducidad y firma de
  autorizaciones. `php artisan mail:test tu@correo.com` para verificar; `MAIL_MAILER=log` para
  desactivar en local.
- **Las invitaciones de acceso NO se envían por correo** (decisión del grupo): `store` /
  `invite` / `resendInvite` generan el token y devuelven el enlace a la vista
  (`session('invite_url')`); coordinación lo copia y lo entrega por WhatsApp / en mano. El
  "¿olvidaste tu contraseña?" del login sí manda correo (`Password::sendResetLink`, en español
  vía `ResetPassword::toMailUsing`).
- Al revisar tu trabajo, haz una pasada tipo
  `grep -rnE 'value="[A-Z][a-z]|>[A-Z][a-z]+ [A-Za-z]|title="[A-Z]' resources/js` para cazar
  texto en inglés que se haya colado.

## Roles y acceso

- Roles funcionales (spatie): `admin` (coordinación, todo vía `Gate::before`), `secretaria`,
  `tesoreria`, `intendencia` (inventario), `responsable` (acotado a `users.branches`), `familia`.
- `familia` **no tiene permisos de módulo**: solo `portal.access`. Todo su acceso se acota por
  parentesco en `App\Http\Controllers\Portal\*` vía `User::children()` (miembros de sus `families`
  con pivote `relationship = hermano`). Nunca le des permisos de gestión.
- Alcance por rama: usa `Member::scopeVisibleTo($user)` y el trait
  `App\Support\ResolvesBranchScope` en las policies. No reimplementes el filtro.
- El portal vive bajo `/portal` (`routes/features/portal.php`, middleware `role:familia`) con su
  propio `PortalLayout.vue`. `RedirectByRole` mantiene a cada rol en su sitio.
- Gestión de cuentas: pantalla **Usuarios** (`/usuarios`, `Admin\UserController`, permiso
  `users.manage` = solo coordinación). Alta de familias desde la ficha de la familia
  (`FamilyAccountController`). No se usa Clerk ni proveedor externo: la tabla `users` local es la
  fuente de verdad (spatie roles, FKs de `families`, `attendances`, etc. dependen de ella).

## Comandos

```bash
php artisan test                     # suite Pest (sqlite :memory:)
php artisan test tests/Feature/<Mod> # por módulo (la suite completa es lenta)
vendor/bin/pint --dirty              # estilo PHP antes de cerrar
npm run build                        # o npm run dev
php artisan migrate                  # sqlite local: database/database.sqlite
```

Logins demo: `admin@grupo.test`, `secretaria@grupo.test`, `tesoreria@grupo.test`,
`intendencia@grupo.test`, `lobatos@grupo.test`, `familia@grupo.test` — todos con `password`.
