# ESTADO.md — Foto actual de la plataforma

> **Para qué sirve este documento.** Es el mapa vivo de lo que ya existe: qué módulos hay, qué
> hacen, dónde viven en el código, y qué está **Hecho / Parcial / Pendiente**. Se actualiza en
> cada cambio funcional (regla en [CLAUDE.md](../CLAUDE.md)). Si vas a empezar algo, mira aquí
> primero para no rehacer lo que ya está.
>
> Última revisión completa: **2026-09-07**.

---

## 1. Resumen en una línea

Plataforma de gestión integral de un grupo scout MSC (Andalucía): **miembros y familias,
tesorería, eventos con validación legal de ratios, secretaría, plan de rama y actividades,
inventario, fotos e historia, web pública y portal de familias**. Laravel 11 + Inertia/Vue 3 +
Tailwind. UI 100 % español, código/BD en inglés. Almacenamiento de ficheros en Google Drive
(nunca en el servidor). ~50 ficheros de tests Pest.

El objetivo de producto es **quitar tiempo a los responsables voluntarios**: cada flujo en el
mínimo de clics, móvil primero.

---

## 2. Roles y acceso (spatie/permission)

| Rol | Alcance | Permisos clave |
|-----|---------|----------------|
| `admin` (coordinación) | Todo, vía `Gate::before` | todos |
| `secretaria` | Miembros (+ datos sensibles), asistencia, eventos, documentos, actas, fotos/historia, inventario (lectura) | `members.*`, `documents.*`, `minutes.*`, `photos.*`, `history.manage` |
| `tesoreria` | Cobros, facturas, informes, ajustes fiscales | `charges.*`, `invoices.*`, `finance.reports`, `settings.finance` |
| `intendencia` | Inventario (gestión + reservas), ver eventos | `inventory.*` |
| `responsable` | **Solo sus ramas** (`users.branches`): miembros sin datos sensibles de otras ramas, asistencia, plan de rama, actividades, inventario (lectura + reservas), eventos, fotos | `members.view`, `attendance.manage`, `events.*`, `plans.*`, `activities.*` |
| `familia` | Solo `portal.access`. Todo se acota por parentesco en `App\Http\Controllers\Portal\*` (`User::children()`, pivote `relationship = hermano`) | `portal.access` |

- Scope por rama: `Member::scopeVisibleTo($user)` + trait `App\Support\ResolvesBranchScope` en policies. **No reimplementar el filtro.**
- Middleware global `RedirectByRole` mantiene a cada rol en su zona (familia → `/portal`, gestión → `/dashboard`).
- Gestión de cuentas: pantalla **Usuarios** (`/usuarios`, `Admin\UserController`, permiso `users.manage`). Las invitaciones **no se mandan por correo** (decisión del grupo): se genera el token y se devuelve el enlace a la vista (`session('invite_url')`) para entregarlo por WhatsApp / en mano.
- Datos de menores = sensibles: lecturas y cambios de fichas médicas/consentimientos se registran con `activitylog` (trait `LogsActivity`, `HealthRecord` redacta valores médicos en el log). Campos médicos **cifrados en reposo** (`casts` `encrypted`).

---

## 3. Módulos — estado por feature

Leyenda: ✅ Hecho · 🟡 Parcial (funciona pero le faltan piezas de workflow) · ⬜ Pendiente.

### 3.1 Miembros y familias — ✅ / 🟡
Controladores: `Members/*` · Páginas: `Pages/Members/*` · Rutas: `routes/features/members.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| CRUD de miembros (censo por rama, KPIs, avatares) | ✅ | `MemberController`, `Pages/Members/Index|Show|Create|Edit` |
| Ficha sanitaria cifrada (`HealthRecord`) | ✅ | alergias, intolerancias, medicación, nº tarjeta sanitaria, observaciones |
| Consentimientos (`Consent`, `ConsentType`) | ✅ | imagen, salidas, datos… editables por miembro |
| Perfil de responsable (`LeaderProfile`) + titulaciones (`LeaderTraining`) | ✅ | certificado de delitos sexuales con fecha de caducidad; alimenta el semáforo legal |
| Familias (`Family`, pivote `family_member` con `relationship`) | ✅ | `FamilyController`, alta/baja de vínculos con miembros |
| Cuentas de portal para familias (`FamilyAccountController`) | ✅ | invitar/revocar; enlace a mano (no correo) |
| Control de asistencia (rejilla móvil, incidencias, guardar en 1 clic) | ✅ | `AttendanceController` + `Pages/Members/Attendance.vue`; `updateOrCreate` idempotente, anti-IDOR. Recuerda la última rama (`localStorage`) y muestra resumen del trimestre (`quarterStats`). |
| Importador CSV de censo (+ plantilla) | ✅ | `MemberImportController` + `MemberImportService` |
| Exportación CSV del censo | ✅ | `MemberController@export` (formato genérico) |
| Campos de censo MSC en la ficha (`dni`, `sex`, `address`) | ✅ | migración `add_census_fields_to_members_table`; en `MemberForm.vue` |
| **Exportación censo oficial MSC** | ✅ | `MemberController@censusMsc` (`/members/censo-msc`): sección, cargo, datos identificativos, contacto, titulación y cert. de delitos sexuales del kraal. Columnas a afinar con el fichero real de la federación. |
| **Firma digital de consentimientos por email** | ✅ | `SignatureController` + flujo público `/publico/firma/{token}` (`Signature`, `SignatureStatus`) |
| **Autoservicio de la familia: revisión de datos + campaña de inicio de curso** | ✅ | Desde `Portal/Child`, un formulario: datos del scout, ficha médica, contacto de la familia, consentimientos (RGPD/imagen/salidas con texto legal) y renovación de plaza ("¿continúa el curso {año}?"). Crea un `MemberChangeRequest` (columna `payload`, grupos `member`/`health`/`family`/`consents`/`renewal`). Secretaría revisa el antes→después y aprueba/rechaza en **Revisiones de familias** (`/revisiones-familias`, `Members\ChangeRequestController`). Al aprobar: escribe `Member`, `HealthRecord` (si `members.sensitive`), `Family`, `Consent` (`signed_at`=hoy) y, si la familia dijo que NO continúa, `active=false`. Aviso en el dashboard. |
| Exportación **censo oficial MSC** (formato requerido) | ⬜ | el export actual es genérico |

### 3.2 Tesorería — ✅ / 🟡
Controladores: `Finance/*` · Páginas: `Pages/Finance/*`, `Pages/Charges/*`, `Pages/Invoices/*`, `Pages/Budgets/*` · Rutas: `routes/features/finance.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Panel de Tesorería (KPIs recaudación, ratio cobrado) | ✅ | `TreasuryDashboardController`, `Pages/Finance/Dashboard` |
| Cobros con reparto por rama/miembro y **descuento hermanos** | ✅ | `ChargeController`, `ChargeAssignmentService`, `Charge`/`ChargeMember` |
| Marcar pagado (individual) + historial por miembro | ✅ | `ChargeMemberController@markPaid`, `Charges/MemberHistory` |
| Marcar pagado **en bloque** (efectivo, tras una salida) | ✅ | `ChargeController@bulkMarkPaid` + `POST /cobros/{charge}/marcar-pagados`; casillas por fila pendiente en `Charges/Show` |
| Recordatorios de pago (Mailable + Job + comando) | ✅ | `charges:send-reminders`, cableado en `schedule` (diario 08:00) |
| Facturas (entrada/salida, categoría, estado) + PDF vía Drive | ✅ | `InvoiceController`, `InvoicePdfService` |
| Presupuestos (`Budget`, `BudgetItem`, estado) + **presupuesto vs. real** | ✅ | `Budget::summary()` (previsto/real/desvío). El informe económico lista cada presupuesto de evento del periodo con su desvío (+ CSV); `Budgets/Show` muestra el desvío del balance |
| Informe económico con gráficas (evolución mensual, desglose por categoría) + export | ✅ | `FinanceReportController`, `FinanceReportService`, `Pages/Finance/Report` |
| Ajustes fiscales (`finance.*` en `Setting`) | ✅ | `SettingsController` |
| Marcar pagado **en bloque** (varios `ChargeMember` a la vez, en efectivo) | ⬜ | hoy es uno a uno; útil tras una salida — ver ROADMAP Épica D |
| Pago online / TPV / declarar pago desde el portal | ❌ descartado | El grupo cobra casi todo **en efectivo** (decisión 2026-09-06). No implementar. |
| Recibo/justificante de pago para la familia | 🟡 | hay PDF de factura; recibo individual solo si alguna familia lo pide |

### 3.3 Calendario y eventos — ✅ / 🟡
Controladores: `Events/*` · Páginas: `Pages/Events/*` · Rutas: `routes/features/events.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| CRUD de eventos, vista **mensual** (rejilla 6 semanas, barras multi-día por carriles) | ✅ | `EventController`, `Pages/Events/Index` |
| Tipos de evento (`EventType`: reunión, salida, acampada, campamento…) | ✅ | |
| Campos MSC (coordinador, ciudad, eucaristía, marcha, temática) | ✅ | migración `add_msc_fields_to_events_objectives_activities` |
| **Validación legal de ratios** (Decretos 45/2000 y 89/2018) con semáforo ✅/⚠️/❌ | ✅ | `CampRatio/CampRatioValidator`, destacado en `Events/Show` |
| Inscripciones (`EventMember` / `EventEnrollmentController`) | ✅ | matrícula por miembro, estado |
| Flujo público de inscripción + autorización `/publico/inscripcion/{token}` | ✅ | `Public/EnrollmentController`; la familia confirma/declina y sube autorización |
| Datos de padre/madre y dirección en la inscripción | ✅ | migraciones `add_parent_data…`, `add_address…` a `event_member` |
| Checklist del evento (`EventChecklistItem`) | ✅ | `EventChecklistController` |
| Cobro asociado al evento (`EventChargeController`) | ✅ | crea un `Charge` desde el evento |
| Disponibilidad de inventario para el evento | ✅ | `InventoryEventAvailabilityController` |
| PDFs: asistentes, circular, autorización individual, ZIP, **dossier**, **lista de material**, **ficha de salida MSC** | ✅ | `EventPdfController`; `events.pdf.materials` (material agregado con cruce de disponibilidad) y `events.pdf.msc-outing` (el impreso oficial de la delegación) |
| Suscripción iCal de solo lectura (token por usuario) | ✅ | `IcalController`, `IcalGenerator`, `User.ical_token` |
| Exportación del calendario a PDF | ✅ | `EventPdfController@calendar` |
| **Reparto de tareas del kraal para la salida** | ✅ | `EventChecklistItem.assigned_to` + `due_at`; se asigna desde `Events/Show` y las tareas sin cerrar salen en el panel de inicio de esa persona (`DashboardController` prop `tasks`). Sin recordatorios automáticos. |
| **Hoja médica/dietética consolidada** de los asistentes a la salida | ✅ | `events.pdf.attendees` (`EventPdfController@attendees`): nombre, contacto, alergias/intolerancias/medicación, estado de pago. Enlazada desde el panel "Preparar salida". |
| **Panel "Preparar salida"** (estado de todos los pasos en `Events/Show`) | ✅ | `EventController@preparationSteps` + sección en `Pages/Events/Show.vue`: 8 pasos con estado ✅/⚠️/⬜, barra de progreso y acción directa (anclas + enlaces). Solo eventos con inscripción y gestor. |
| Circular genérica a las familias | ❌ aplazado | Hoy no se usa; lo que la familia necesita (la autorización) ya funciona con el enlace público + PDF. Solo si el grupo pide una circular con diseño. |

### 3.4 Secretaría — ✅
Controladores: `Secretary/*` · Páginas: `Pages/Documents/*`, `Pages/Minutes/*` · Rutas: `routes/features/secretary.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Documentos del grupo (`Document`, `DocumentCategory`) con caducidad | ✅ | seguros, censo, permisos; `expiringWithin()` alimenta el dashboard |
| Firma digital de documentos por email | ✅ | `SignatureController@sendDocument` + flujo público |
| Actas (`Minute`, `MinuteItem`) + PDF | ✅ | `MinuteController`, `MinutePdfService` |
| Estructura de carpetas en Drive (`DriveStructureService`) | ✅ | crea el árbol SECRETARÍA / TESORERÍA / … |
| **Memoria del curso** | ✅ | `Reports\AnnualReportController` (`/memoria`, `/memoria/pdf`) + `AnnualReportService`: objetivos por plan de rama, eventos realizados, asistencia media y censo, por curso escolar. Página + PDF. Acotada por rama. Ver §3.11. |

### 3.5 Plan de rama y actividades — ✅
Controladores: `Plans/*` · Páginas: `Pages/BranchPlans/*`, `Pages/Activities/*` · Rutas: `routes/features/plans.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Plan de rama (`BranchPlan`) con objetivos (`BranchPlanObjective`, área de desarrollo, estado) | ✅ | `BranchPlanController`, progreso por trimestre en `Show` |
| Biblioteca de actividades (`Activity`, `ActivityMaterial`) | ✅ | CRUD + duplicar (`ActivityDuplicator`) |
| Vincular actividad ↔ evento y actividad ↔ objetivo | ✅ | `ActivityScheduleController`, `ActivityObjectiveController` |
| Campos MSC de actividad (día, franja, nº, materiales en texto) | ✅ | migración MSC |
| Material agregado de un evento (suma de `ActivityMaterial` de sus actividades) | ✅ | `App\Services\Events\EventMaterialList`; PDF `events.pdf.materials` + tabla en el dossier |
| Generación del calendario del trimestre desde el plan | ✅ | `BranchPlanScheduleController@store` (`POST /branch-plans/{plan}/reuniones`): crea las reuniones semanales de un tramo en bloque, con vista previa en `BranchPlans/Show`. No duplica ni pisa `skip_dates`. |
| **Formato oficial MSC del plan de rama** | ✅ | Catálogo de la delegación en `App\Support\MscPlanCatalog` (Ámbito → Línea → Contenido) + enums `MscScope` y `ActivityType`. El objetivo se rellena como en el impreso: ámbito, línea, contenido, "¿cómo estamos?", verbo + complemento y "¿cómo ha salido?" (`branch_plan_objectives.scope/line/current_situation/goal_verb/goal_complement/evaluation`). Desplegables encadenados en `BranchPlans/Show`. |
| **Hoja de programación trimestral (PDF)** | ✅ | `Plans\BranchPlanPdfController@term` (`/branch-plans/{plan}/pdf/trimestre/{term}`) + `App\Services\Plans\TermPlanSheet` + `pdf/branch-plan-term.blade.php`: tabla de ámbitos, calendario del trimestre (reuniones y salidas de la rama), evaluación y ficha de cada actividad. Enlace en cada tarjeta de trimestre. |
| **Ficha de salida oficial MSC (PDF)** | ✅ | `Events\EventPdfController@mscOuting` (`/eventos/{event}/pdf/ficha-salida`) + `App\Services\Events\MscOutingSheet` + `pdf/event-msc-outing.blade.php` (membrete compartido `pdf/partials/msc-letterhead`): DATOS SALIDA (responsable, curso, grupo, rama, niños, responsables con/sin titulación, fecha y lugar), las tres tablas de ámbito y la rejilla ESTRUCTURA por día y franja. Enlace en `Events/Show`. |
| **Actividades y objetivos en la ficha del evento** | ✅ | Sección "Actividades y objetivos del plan" en `Events/Show`: cada actividad enlazada con su tipo, encargado, ámbito y objetivo del plan, aviso de lo que le falta para salir en el impreso (objetivo, franja, día, número), botón **Enlazar actividad** (modal sobre `activities.events.attach`) y quitar del evento. Cuatro contadores de preparación (`EventController@mscReadiness`). |
| Datos de prueba del formato MSC | ✅ | `database/seeders/MscDemoSeeder.php` (`php artisan db:seed --class=MscDemoSeeder`): plan Ranger 2025-2026 con los objetivos del primer y tercer trimestre, cinco actividades con franja y número, acampada de inauguración con 14 educandos y 4 responsables, y todo enlazado. Idempotente. |
| Campos MSC de actividad ampliados (tipo, encargado, fecha, sitio, evaluación) | ✅ | `activities.activity_type/owner/scheduled_date/place/evaluation`; en los formularios de actividad y en su ficha. |

### 3.6 Inventario — ✅
Controladores: `Inventory/*` · Páginas: `Pages/Inventory/*` · Rutas: `routes/features/inventory.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Ítems (`InventoryItem`, `InventoryCategory`, `ItemCondition`) con KPIs y alertas de revisión | ✅ | `InventoryItemController`, `needingReview()` |
| Reservas / préstamos (`Checkout`) con fecha esperada de devolución y "fuera de plazo" | ✅ | `CheckoutController`, `Checkout::outstanding()` |
| Disponibilidad por evento / por ítem | ✅ | `InventoryAvailabilityService` |
| Barra de disponibilidad y modal de reserva pulidos | ✅ | |

### 3.7 Fotos e historia — ✅
Controladores: `Photos/*` · Páginas: `Pages/Albums/*`, `Pages/History/*` · Rutas: `routes/features/photos.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Álbumes y fotos (`Album`, `Photo`) en Drive | ✅ | `AlbumController`, `PhotoController` |
| Galería pública `/galeria` (solo `visibility = publishable`, respeta consentimiento de imagen) | ✅ | |
| Línea de tiempo de historia (`HistoryEntry`) + página pública `/historia` | ✅ | `HistoryEntryController`, `HistoryPublicController` |

### 3.8 Web pública — ✅
Controlador: `Public/HomeController` · Layout: `PublicLayout.vue` · Config: `config/group.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| Portada `/` (quiénes somos, secciones por edad, qué hacemos, galería, historia, únete, FAQ, contacto) | ✅ | todo el texto editable en `config/group.php`, sin tocar Vue |
| SEO + JSON-LD (`Organization` / `LocalBusiness`) | ✅ | en `app.blade.php` |
| Capa de movimiento (cabecera que se contrae, `v-reveal`, contadores, acordeón FAQ) | ✅ | respeta `prefers-reduced-motion` |
| Fotos en `public/images/landing/` con fallback ilustrado | ✅ | valida firma de fichero (ignora HEIC) |

### 3.9 Portal de familias — 🟡
Controladores: `Portal/*` · Páginas: `Pages/Portal/*` · Layout: `PortalLayout.vue` · Rutas: `routes/features/portal.php` (prefijo `/portal`, `role:familia`)

| Elemento | Estado | Notas |
|----------|--------|-------|
| Inicio del portal (pagos pendientes + próximos eventos de sus ramas) | ✅ | `Portal/DashboardController` |
| Ver pagos: pendientes e histórico (**solo lectura**) + datos para pagar | ✅ | `Portal/PaymentController`; suficiente — el grupo cobra en efectivo |
| Calendario + suscripción iCal propia | ✅ | `Portal/CalendarController` |
| Ficha del hijo (`Portal/ChildController`) | ✅ | solo lectura |
| Pagar online / declarar pago | ❌ descartado | El grupo cobra en efectivo (decisión 2026-09-06) |
| Firmar autorizaciones desde el portal | ⬜ baja | El flujo por enlace de correo ya cubre la necesidad; solo si se ve que las familias con cuenta lo prefieren |
| **Revisar datos del hijo + campaña de inicio de curso** (datos, ficha médica, contacto familia, consentimientos, renovación de plaza) | ✅ | `Portal/Child` con formulario único; crea `MemberChangeRequest`. Aprobación en `/revisiones-familias`. Ver §3.1. |

### 3.10 Panel de inicio (gestión) — ✅
`DashboardController` · `Pages/Dashboard.vue`

- Prioriza **lo accionable**: `attention` (avisos con gravedad + enlace directo a donde se resuelve;
  incluye "Revisiones de familias" pendientes), **`week`** ("Tu semana": rejilla lun–dom con los
  eventos de las ramas del usuario + tareas de kraal que vencen esta semana), `tasks` (tareas de
  kraal sin cerrar), `agenda`, `finance` (pendiente/cobrado/ratio), `inventory`, `members`.
- Avisos actuales: certificados de delitos sexuales por caducar, autorizaciones sin entregar del
  próximo evento, titulaciones por caducar, cuotas pendientes, documentos por caducar, préstamos
  fuera de plazo. Todo acotado a las ramas del usuario si es responsable.
- **Ctrl+K** (`CommandPalette`): salto rápido a cualquier pantalla o acción.
- `AppLayout`: barra lateral plegable (preferencia en `localStorage`), buscador y título de sección.

### 3.11 Informes / cierre de curso — ✅
Controlador: `Reports\AnnualReportController` · Servicio: `AnnualReportService` · Rutas: `routes/features/reports.php`

| Elemento | Estado | Notas |
|----------|--------|-------|
| **Memoria del curso** (`/memoria` + `/memoria/pdf`) | ✅ | por `school_year`: objetivos por plan de rama (total/logrados/% y por ámbito), eventos realizados globales y por rama, asistencia media a reuniones, censo actual. `Pages/Reports/AnnualReport.vue` + `pdf/annual-report.blade.php`. Permiso `plans.view`; responsable acotado a sus ramas. |
| **Censo oficial MSC** | ✅ | `MemberController@censusMsc` (ver §3.1). |
| **Presupuesto vs. real** | ✅ | en el informe económico y en la ficha del presupuesto (ver §3.2). |
| **Altas y bajas del curso** (retención) | ✅ | `AnnualReportService@retention`: altas por `joined_at`, bajas por `members.left_at` (columna nueva que mantiene sola `Member::booted`), + balance neto. En la página y el PDF de la memoria. |
| Cargar contenido en la memoria (fotos destacadas, texto narrativo) | ⬜ | hoy es solo cuantitativa |

---

## 4. Infraestructura y transversales

| Área | Estado | Detalle |
|------|--------|---------|
| Sistema de diseño compartido | ✅ | `Components/Shared/*`: `PageHeader`, `DataTable`, `AppButton`, `StatCard`, `SectionCard`, `EmptyState`, `SearchInput`, `FilterSelect`, `DashIcon`, `Modal`, `FormField`, `BadgeEstado`, `BadgeRama`, `ConfirmButton`. Tokens en `app.css` + `tailwind.config.js`. **Úsalos, no dupliques marcado.** |
| Toasts | ✅ | `useToast()` → `toast.success(...)`. Nada de `alert()`. |
| Almacenamiento de ficheros | ✅ | `DriveServiceInterface` (inyectado). `GoogleDriveService` (REST + JWT de service account, sin SDK) / `FakeDriveService` (local/tests). En BD solo `drive_file_id` + metadatos. |
| Correo | ✅ | SMTP Gmail (Workspace del grupo). Recordatorios de pago, avisos de caducidad, firma. `php artisan mail:test`. `MAIL_MAILER=log` para desactivar en local. Correos del framework (reset/verify) sobrescritos en español en `AppServiceProvider::boot()`. |
| Colas | ✅ | driver `database`; `queue:work` (servicio `queue` en Docker). |
| Scheduler | ✅ | `routes/console.php`: `charges:send-reminders` (08:00), `alerts:expiry` (08:15). Un cron ejecuta `schedule:run`. |
| Seguridad | ✅ | middleware `SecurityHeaders` + HSTS en prod; scope Drive reducido a `drive.file`; campos médicos cifrados; `activity_log` con retención 365 días; checklist de producción RGPD art. 9 en el README. |
| Tests | ✅ | ~50 ficheros Pest (SQLite en memoria, `FakeDriveService`). Cada módulo incluye test de autorización cruzada (responsable de otra rama → 403). Comandos: `php artisan test` / `php artisan test tests/Feature/<Mod>`. |
| i18n | ✅ | `lang/es/*.php` para servidor; texto español directo en plantillas Vue (patrón del proyecto). Páginas de auth/perfil de Breeze ya traducidas. |
| Despliegue | ✅ | Docker Compose (dev) + guía Forge/VPS en README. `opcache` afinado para Docker/Windows. |

### Enums de dominio (`app/Enums/`)
`UserRole`, `MemberRole`, `EventType`, `ChargeStatus`, `ChargeType`, `PaymentMethod`,
`InvoiceCategory`, `InvoiceDirection`, `InvoiceStatus`, `BudgetStatus`, `ConsentType`,
`DocumentCategory`, `FamilyRelationship`, `InventoryCategory`, `ItemCondition`,
`LeaderQualification`, `ObjectiveStatus`, `SignatureStatus`.

### Modelos (`app/Models/`)
`User`, `Member`, `HealthRecord`, `Consent`, `LeaderProfile`, `LeaderTraining`, `Family`,
`Event`, `EventMember`, `EventChecklistItem`, `Attendance`, `Charge`, `ChargeMember`, `Invoice`,
`Budget`, `BudgetItem`, `Document`, `Minute`, `MinuteItem`, `InventoryItem`, `Checkout`,
`BranchPlan`, `BranchPlanObjective`, `Activity`, `ActivityMaterial`, `Album`, `Photo`,
`HistoryEntry`, `Signature`, `Setting`.

---

## 5. Deuda técnica / cosas a vigilar

- `composer.json`: `config.policy.advisories.block = false` a propósito (activarlo rompe la instalación de Laravel 11 en este entorno). Revisar avisos con `composer audit` manualmente.
- Presupuestos (`Budget`) están construidos pero poco conectados al informe económico y al evento.
- El grupo usa WhatsApp para comunicarse con las familias y **no quiere** una circular genérica en la app (2026-09-06). Reevaluar solo si piden una circular con diseño de marca.
- No hay centro de notificaciones in-app ni push; todo va por correo + dashboard (por ahora suficiente).

---

## 6. Cómo mantener este documento

Al terminar cualquier cambio funcional, actualiza la tabla del módulo afectado (🟡/⬜ → ✅, o
añade fila nueva) con una frase de qué hace y el controlador/página/ruta donde vive. Es la regla
de [CLAUDE.md](../CLAUDE.md). Si el cambio toca esquema o modelos compartidos, añade también fila
en [CHANGES.md](../CHANGES.md).
