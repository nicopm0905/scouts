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

## 7. Frontend (Vue 3 + Inertia)
- Componentes compartidos en `resources/js/Components/Shared/`: **úsalos, no los dupliques**.
  - `DataTable.vue` — tabla con búsqueda instantánea, filtros persistentes (query string), paginación.
  - `Modal.vue`, `FormField.vue`, `BadgeEstado.vue`, `ConfirmButton.vue`, `PageHeader.vue`.
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
