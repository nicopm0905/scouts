# Plataforma de gestión — Grupo Scout (MSC Andalucía)

Aplicación web para la gestión integral de un grupo scout del Movimiento Scout Católico:
miembros y familias, tesorería, calendario y eventos (con validación legal de ratios),
secretaría (documentos y actas), planes de rama y actividades, inventario, fotos e historia.
Objetivo: **ahorrar tiempo a los responsables voluntarios** — cada flujo en el mínimo de clics,
móvil primero.

## Stack

- **Backend:** Laravel 11 (PHP 8.3), PostgreSQL 16
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS (SPA, SSR desactivado)
- **Auth y permisos:** Laravel Breeze + spatie/laravel-permission
- **Auditoría:** spatie/laravel-activitylog (accesos a fichas de menores)
- **PDF:** barryvdh/laravel-dompdf (facturas, recibos, listados, circulares, actas)
- **Almacenamiento:** Google Drive (service account) vía `DriveServiceInterface`. Los ficheros
  NO se guardan en el servidor: solo metadatos y `file_id`. Driver `fake` para local/tests.
- **Colas y scheduler:** Laravel queues (driver database), cron → `schedule:run`
- **Tests:** Pest (SQLite en memoria)

Idioma: **UI en español**, código y base de datos en inglés.

---

## Puesta en marcha con Docker (recomendado)

Requisitos: Docker Desktop.

```bash
# 1. Clonar y entrar
git clone <repo> scouts && cd scouts

# 2. Copiar variables de entorno
cp .env.example .env          # revisa credenciales de Drive (abajo)

# 3. Construir e iniciar (app en :8000, vite en :5173, postgres en :5433)
docker compose build
docker compose up -d db
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed   # datos de demo realistas
docker compose up -d app vite queue

# App disponible en http://localhost:8000
```

> En Windows/Git Bash, antepón `export MSYS_NO_PATHCONV=1` para evitar la conversión de rutas.

### Usuarios de demostración (contraseña `password`)

| Email | Rol |
|-------|-----|
| admin@grupo.test | Coordinación (todo) |
| secretaria@grupo.test | Secretaría |
| tesoreria@grupo.test | Tesorería |
| lobatos@grupo.test | Responsable (rama Lobatos) |
| pioneros@grupo.test | Responsable (rama Pioneros) |

Los datos de demo incluyen ~71 miembros, 8 responsables, 3 familias, un **campamento que
no cumple la ratio legal** (para ver el validador en rojo), cobros mixtos, 20 actividades y
30 ítems de inventario.

### Comandos útiles (contenedor)

```bash
docker compose run --rm app php artisan test          # suite Pest
docker compose run --rm app php artisan migrate:fresh --seed
docker compose run --rm app php artisan schedule:list
docker compose exec app php artisan tinker
```

---

## Instalación nativa (sin Docker)

Requiere PHP 8.3 (ext: pdo_pgsql, mbstring, gd, zip, intl, bcmath), Composer 2, Node 20+,
PostgreSQL 15+.

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# configura DB_* en .env apuntando a tu Postgres
php artisan migrate --seed
php artisan serve         # y en otra terminal: npm run dev
```

---

## Configuración de Google Drive (producción)

El almacenamiento usa una **cuenta de servicio** de Google Cloud con la API de Drive habilitada.

1. En Google Cloud Console: crea un proyecto, habilita **Google Drive API**, crea una
   **cuenta de servicio** y descarga su clave JSON.
2. En Google Drive, crea una carpeta raíz y **compártela** (editor) con el email de la cuenta
   de servicio (`...@...iam.gserviceaccount.com`). Copia el ID de la carpeta (de su URL).
3. Configura en `.env`:

```dotenv
DRIVE_DRIVER=google
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=/ruta/segura/credenciales.json   # ruta al fichero o el JSON en crudo
GOOGLE_DRIVE_ROOT_FOLDER_ID=xxxxxxxxxxxxxxxxxxxxx
```

En local y en los tests se usa `DRIVE_DRIVER=fake` (no toca la red). La implementación real
(`app/Services/Drive/GoogleDriveService.php`) usa la API REST + un JWT de cuenta de servicio
(`firebase/php-jwt`), sin el SDK pesado. Para cambiar de proveedor, implementa
`DriveServiceInterface` y ajusta el binding en `DriveServiceProvider`.

---

## Colas y tareas programadas (scheduler)

**Cola** (emails, sincronización con Drive):

```bash
php artisan queue:work --tries=3    # en Docker ya corre el servicio "queue"
```

**Cron** (una sola línea en el servidor ejecuta todo el scheduler):

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Tareas programadas (ver `routes/console.php`):

- `charges:send-reminders` — recordatorios de pago (X días antes del vencimiento y al vencer). Diario 08:00.
- `alerts:expiry` — avisos de documentos, certificados de delitos sexuales e inventario próximos a caducar/revisar. Diario 08:15.

Configura el correo (`MAIL_*`) en `.env` para el envío real.

---

## Despliegue en VPS con Laravel Forge

1. Servidor con PHP 8.3 + PostgreSQL. Crea un sitio y conecta el repositorio.
2. Variables de entorno del panel: `APP_*`, `DB_*`, `MAIL_*`, `DRIVE_*` (ver arriba).
3. Script de despliegue:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   npm ci && npm run build
   php artisan config:cache && php artisan route:cache
   php artisan queue:restart
   ```
4. Activa el **Scheduler** de Forge (equivale al cron de arriba) y un **worker de cola**
   (Daemon: `php artisan queue:work --tries=3`).

---

## Roles y permisos

| Rol | Alcance |
|-----|---------|
| `admin` (coordinación) | Todo |
| `secretaria` | Miembros, documentos, actas, censo, calendario, fotos/historia |
| `tesoreria` | Cobros, pagos, facturas, informes económicos, ajustes fiscales |
| `responsable` | Solo su(s) rama(s): miembros (sin datos sensibles de otras ramas), asistencia, plan de rama, actividades, inventario (lectura + reservas), eventos de su rama |
| `familia` (fase 2) | Preparado: ver/pagar cobros, autorizaciones, calendario, circulares |

La autorización se aplica con **policies** en todos los recursos. El scope por rama del rol
`responsable` se implementa en `Member::scopeVisibleTo()` y las policies. Los accesos a datos
sensibles de menores (ficha médica, consentimientos) se registran con activitylog.

## Validación legal de actividades (Andalucía)

`app/Services/CampRatio/CampRatioValidator.php` implementa las ratios de los Decretos 45/2000
y 89/2018: 1 responsable por cada 10 participantes (mayoría < 12 años) o por cada 15 (≥ 12),
al menos un **director**, máximo 33% de responsables en prácticas, y avisos por certificados de
delitos sexuales caducados/ausentes. Resultado en semáforo ✅/⚠️/❌ en la página del evento.

---

## Estructura y convenciones

Ver **`CONVENTIONS.md`** (rutas por feature autocargadas desde `routes/features/*.php`,
componentes compartidos en `resources/js/Components/Shared/`, textos en `lang/es/`, tests Pest
por módulo). Cambios aditivos al esquema documentados en **`CHANGES.md`**.

## Tests

```bash
docker compose run --rm app php artisan test           # toda la suite
docker compose run --rm app php artisan test tests/Feature/Members   # un módulo
```

Los tests usan SQLite en memoria y el `FakeDriveService` (sin red). Incluyen pruebas de
autorización cruzada (un `responsable` de otra rama recibe 403) en cada módulo.
