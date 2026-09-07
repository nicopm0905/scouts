# CONVENTIONS.md — Reglas para todos los agentes

Documento de contrato compartido. **Léelo antes de escribir código.** El orquestador (Fase 0)
crea el núcleo (migraciones, modelos, enums, policies, servicios, layout base). Los agentes de
feature (Fase 1) construyen encima **sin modificar** ese núcleo.

## 1. Idioma
- **Código, nombres de BD, rutas, clases, variables → inglés.**
- **Toda la UI visible → español.** Textos en `lang/es/*.php`, nunca hardcodeados en componentes.
- Comentarios: español permitido.

## 2. Estructura de carpetas
```
app/
  Enums/                 # Enums compartidos (NO modificar; añadir solo con migración aditiva documentada)
  Models/                # Modelos Eloquent compartidos (NO modificar la firma de relaciones existentes)
  Policies/              # Una policy por modelo
  Services/
    Drive/               # DriveServiceInterface + implementaciones
    CampRatioValidator.php
  Http/
    Controllers/<Feature>/    # Controladores agrupados por feature
    Requests/<Feature>/       # Form Requests
    Resources/                # API/Inertia resources si aplica
resources/
  js/
    Pages/<Feature>/     # Páginas Inertia por feature (PascalCase)
    Components/Shared/    # Componentes reutilizables (los crea Fase 0; reutilízalos)
    Components/<Feature>/ # Componentes propios de un feature
    Layouts/
lang/es/                 # Traducciones
routes/
  web.php                # Incluye los ficheros por feature
  features/<feature>.php # Un fichero de rutas por feature
tests/
  Feature/<Feature>/     # Tests Pest por feature
```

## 3. Rutas
- Nombres de ruta con punto: `members.index`, `charges.store`, `events.enrollments.confirm`.
- Recursos RESTful (`Route::resource`) siempre que encaje.
- Rutas públicas tokenizadas bajo prefijo `/publico/{token}` sin auth (inscripciones, iCal).
- Cada feature registra sus rutas en `routes/features/<feature>.php` e incluidas desde `web.php`
  dentro del grupo `auth` (salvo las públicas).

## 4. Autorización
- **Toda** acción pasa por una Policy. Nada de lógica de permisos suelta en controladores.
- El rol `responsable` está limitado a **sus ramas** (`user->branches`). Usar el scope
  `Member::scopeVisibleTo($user)` y equivalentes que provee el núcleo. NUNCA saltárselo.
- Datos de menores = sensibles: las lecturas de fichas se registran con activitylog
  (helper `LogsAccess` en el núcleo).

## 5. Base de datos / migraciones
- **No editar** migraciones del núcleo. Si necesitas un campo, crea una migración **aditiva**
  (`add_x_to_y_table`) y anótala en `CHANGES.md` con el motivo.
- Nombres: tablas en plural snake_case; pivotes en singular alfabético (`charge_member`).
- Enums de dominio → columnas `string` + cast al Enum PHP en el modelo. No usar enums nativos de Postgres.
- Timestamps siempre. Soft deletes solo donde se indique.
- Dinero: columna `decimal(10,2)`. Fechas de negocio: `date`; instantes: `timestamp`.

## 6. Modelos
- Casts de enums declarados en `$casts`. Relaciones tipadas con return types.
- No romper relaciones existentes. Añadir relaciones nuevas es libre.

## 6bis. Formato MSC (plan de rama, actividades y salidas)
- El plan de rama y la ficha de salida siguen el **impreso de la Delegación Diocesana del MSC**.
  No inventes campos ni etiquetas: si algo va al papel, tiene que llamarse como en el impreso.
- El catálogo oficial **Ámbito → Línea → Contenido** vive en `App\Support\MscPlanCatalog`
  (con los verbos de objetivo y las franjas horarias). Está en código, no en base de datos, porque
  lo fija la federación. `forFrontend()` es lo que consumen los desplegables encadenados.
- Un objetivo se redacta como en el impreso: verbo + complemento. `BranchPlanObjective::goalText()`
  es la forma correcta de leerlo; `description` guarda la frase compuesta por compatibilidad.
- `activities.time_slot` guarda las **claves** del catálogo (`manana`, `tarde_1`, `tarde_2`,
  `noche`), no la etiqueta visible. Usa `MscPlanCatalog::timeSlotLabel()` para pintarla.
- Los impresos se arman en servicios (`Plans\TermPlanSheet`, `Events\MscOutingSheet`) y se pintan
  en blades bajo `resources/views/pdf/`. La vista no consulta la base de datos.
- El membrete oficial (logo, sellos, texto legal vertical y pie de la delegación) es el parcial
  `pdf/partials/msc-letterhead`. Inclúyelo en cualquier PDF que vaya a papel y reserva el hueco en
  `@page`: ~53mm arriba, 28mm a la izquierda y 15mm abajo. En apaisado pásale `['mscLandscape' => true]`.

## 7. Frontend (Vue 3 + Inertia)
- Componentes compartidos en `resources/js/Components/Shared/`: **úsalos, no los dupliques**.
  Son el sistema de diseño de la plataforma; si escribes marcado propio, la pantalla se sale del estilo común.
  - `PageHeader.vue` — título, subtítulo, `icon` de sección y slots `actions` / `meta`.
  - `DataTable.vue` — tabla con búsqueda instantánea, orden, filtros persistentes (query string) y
    modo tarjeta automático en móvil. Slots: `cell-<key>`, `actions`, `filters`, `empty`.
  - `AppButton.vue` — botón único (variantes primary/secondary/ghost/danger, tamaños sm/md, `icon`).
  - `StatCard.vue` — tarjeta de cifra (KPI) con tono e icono; admite `href` para hacerla clicable.
  - `SectionCard.vue` — caja de contenido con cabecera (icono, título, acciones).
  - `EmptyState.vue`, `SearchInput.vue`, `FilterSelect.vue`, `DashIcon.vue` (iconos de línea).
  - `Modal.vue`, `FormField.vue`, `BadgeEstado.vue`, `BadgeRama.vue`, `ConfirmButton.vue`.
  - Nada de emojis como iconos de interfaz: usa `DashIcon`.
  - Toasts: `useToast()` (composable) → `toast.success('Guardado')`. Nada de `alert()`.
- **Móvil primero.** Tailwind, breakpoints `sm/md/lg`. Acciones frecuentes ≤ 2 clics.
- Nada de páginas de confirmación innecesarias: usar toast + deshacer donde aplique.
- Formularios con `useForm` de Inertia. Errores de validación vienen del backend.

## 7bis. Almacenamiento de ficheros
- NUNCA guardar ficheros en el servidor. Usar el `DriveServiceInterface` (inyectado).
- En BD se guarda solo `drive_file_id` / `external_url` + metadatos.
- En tests, el contenedor bindea `FakeDriveService` (no toca red).

## 8. Tests (Pest)
- Feature tests de los flujos críticos de tu módulo antes de cerrar la feature.
- Usar factories del núcleo. Base de datos con `RefreshDatabase`.
- Incluir al menos un test de **autorización** (un `responsable` de otra rama recibe 403).

## 9. Git
- Cada agente trabaja en `feature/<nombre>`. Commits pequeños y descriptivos.
- No tocar ficheros de otro feature. Conflictos previstos → coordinar vía `CHANGES.md`.

## 10. Añadir campos/tablas nuevas (proceso)
1. Migración aditiva nueva (no editar las del núcleo).
2. Actualizar el modelo (añadir a `$fillable`/`$casts`, relación nueva).
3. Anotar en `CHANGES.md`: qué, por qué, qué agentes se ven afectados.
