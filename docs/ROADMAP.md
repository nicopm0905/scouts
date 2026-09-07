# ROADMAP.md — Plan por fases

> **Criterio de priorización (único).** Cada punto se ordena por **minutos/mes que le ahorra a un
> responsable voluntario** (o a secretaría/tesorería), no por dificultad ni por "quedaría bien".
> Móvil primero. Acción frecuente ≤ 2 clics. Cero doble tecleo: un dato se introduce una vez y
> viaja entre módulos.
>
> Marca `[x]` cuando algo se complete y muévelo también en [ESTADO.md](ESTADO.md).
> Última revisión: **2026-09-06**.

---

## 1. Dónde se va el tiempo hoy (diagnóstico)

Los responsables repiten 7 trabajos a lo largo del curso. Esto es lo que cada uno cuesta **hoy** y
por qué:

| Trabajo recurrente | Frecuencia | Fricción actual | Fase que lo ataca |
|--------------------|-----------|-----------------|-------------------|
| **Pasar lista** de la reunión | Semanal | El modo móvil rápido **ya existe** (rejilla, contador, incidencias, guardar en 1 clic); solo falta pulir defaults/plantilla de sesión | Fase 3 · Épica A (casi hecha) |
| **Preparar una salida / campamento** | Mensual | Muchos pasos sueltos: crear evento → inscribir → pedir autorizaciones → validar ratio → reservar material → cobrar → hoja médica. Cada uno en su pantalla | **Fase 3 · Épica C (prioridad nº 1)** |
| **Cobrar la cuota** (mayoría **en efectivo**) | Trimestral + goteo | Se cobra en mano y se marca pagado a mano miembro a miembro; no hay "cobrar a los inscritos" de un evento en 1 paso | Fase 3 · Épica D (reducida) |
| **Recoger y actualizar datos** de las familias (inicio de curso, alergias, consentimientos) | Anual + puntual | Se rehace importando CSV; la familia no puede editar nada; secretaría teclea todo | Fase 4 · Épica E |
| **Planificar el trimestre** (plan de rama → actividades → calendario) | Trimestral | El plan y las actividades existen, pero los eventos del trimestre se crean uno a uno a mano; no hay ficha de sesión imprimible | Fase 4 · Épica F |
| **Cerrar el curso** (memoria, censo oficial MSC, informe económico) | Anual | Informe económico ✅; memoria y **censo oficial MSC** se hacen fuera de la plataforma | Fase 4 · Épica G |

---

## 2. Fase 3 — "Un flujo, no ocho pantallas" (prioridad ALTA)

Ajustado con el grupo (2026-09-06): **no** hace falta avisar a las familias de las faltas (ya lo
gestionan por WhatsApp), **no** hace falta pago online ni conciliación (se cobra casi todo en
efectivo), y la **circular** genérica por ahora no se usa — lo que la familia necesita es la
**autorización**, que ya funciona con el enlace público + PDF. El foco de la fase es **encadenar
los pasos de preparar una salida en una sola pantalla**.

**Estado (2026-09-06): Fase 3 completada.** Épicas A, C y D (reducida) ✅; B aplazada por decisión
del grupo; deuda y repaso móvil hechos. Siguiente: Fase 4.

### Épica A — Pasar lista ✅
El modo móvil ya estaba: `Pages/Members/Attendance.vue` tiene rejilla, chips de rama, selector de
fecha, "todos presentes/ausentes", contador en vivo, incidencias por scout y botón fijo de guardar
(`AttendanceController` con `updateOrCreate` idempotente y anti-IDOR). Pulido añadido:
- [x] **Última rama recordada**: si entras a Asistencia sin elegir rama, salta a la que pasaste
      lista la última vez (`localStorage`, prop `branchExplicit`). La fecha ya venía en "hoy".
- [x] **Resumen del trimestre** en la propia pantalla: nº de reuniones y % de asistencia media de
      la rama (`AttendanceController@quarterStats`, prop `stats`).
- ~~Aviso automático de falta a la familia~~ — **descartado por el grupo** (ya lo gestionan ellas).
- **Hecho cuando:** el responsable entra en Asistencia y ya está en su rama y en la fecha de hoy,
  sin tocar filtros. ✅

### Épica B — Circular con diseño (APLAZADA — solo si el grupo decide usarla)
Hoy no se usa una circular genérica. Si más adelante se quiere una circular "bonita" (logo del
grupo, imagen, datos del evento) para mandar por WhatsApp/correo, se retoma aquí:
- [ ] Plantilla PDF de circular con identidad del grupo (logo, colores) reutilizando `config/group.php`.
- [ ] Botón "Generar circular" en `Events/Show` que produzca ese PDF a partir de los datos del evento.
- [ ] (Si se pide) envío asistido por WhatsApp: texto + `wa.me` con los teléfonos de las familias de
      la rama (mismo patrón que las invitaciones de acceso).
- **No se empieza** hasta que el grupo lo pida explícitamente.

### Épica C — Asistente de salida (PRIORIDAD Nº 1)
- [x] **Panel "Preparar salida"** dentro de `Events/Show`: checklist guiado con el estado de cada
      paso (✅ hecho / ⚠️ a medias / ⬜ pendiente), barra de progreso "X/Y listo" y acción directa
      por paso (datos y ramas, semáforo de ratio, inscripciones, autorizaciones, material,
      cobro, hoja médica, checklist de documentación). `EventController@preparationSteps` +
      sección nueva en `Pages/Events/Show.vue` con anclas a cada bloque. Solo para eventos con
      inscripción y usuarios que pueden gestionar el evento.
- [x] **Hoja médica de asistentes** — ya cubierta por `events.pdf.attendees`
      (`EventPdfController@attendees`): nombre, teléfono de contacto, alergias/intolerancias/
      medicación y estado de pago. Enlazada como paso del panel. *(Falta opcional: vista en
      pantalla para el móvil, sin PDF.)*
- [x] **"Cobrar a los inscritos" en 1 paso**: `EventChargeController` ya crea el `Charge` y lo
      reparte solo entre los `EventMember` con `enrolled = true`. El paso "cobro" del panel enlaza
      directo al cobro cuando existe.
- [x] **Reparto de tareas del kraal**: cada `EventChecklistItem` admite `assigned_to` + `due_at`
      (migración aditiva). Se asigna desde `Events/Show` (checklist) y las tareas sin cerrar
      aparecen en el panel de inicio de esa persona (`DashboardController` prop `tasks`). Sin
      recordatorios automáticos por ahora.
- **Hecho cuando:** montar una acampada (evento + inscripción + material + cobro + hoja médica) se
  hace desde una sola pantalla y se ve de un vistazo qué falta. ✅

### Épica D — Cobro de eventos más rápido (reducida)
Sin pago online ni conciliación (el grupo cobra en efectivo). Solo:
- [x] **Marcar pagado en bloque** desde `Charges/Show`: casillas por fila pendiente + "seleccionar
      los N pendientes" + método (efectivo por defecto) → `POST /cobros/{charge}/marcar-pagados`
      (`ChargeController@bulkMarkPaid`). Ignora filas de otro cobro y las ya pagadas.
- [ ] (Opcional) **Recibo individual** en PDF para la familia que lo pida, reutilizando dompdf.
- ~~Pago online / declarar pago / bandeja de conciliación / TPV~~ — **descartado por el grupo**.
- **Hecho cuando:** tras una salida, el responsable/tesorería marca a los 20 que pagaron en efectivo
  en una sola acción. ✅

### Transversal de Fase 3
- [x] Limpiar deuda: borrados `test_drive.php`, `tests/Feature/ExampleTest.php` y `tests/Unit/ExampleTest.php`.
- [x] Repaso móvil (360 px) de asistencia, evento y cobros: los selects/inputs nuevos llevan
      `min-w-0` + `flex-wrap` para no generar barra horizontal; el resto ya era responsive.

---

## 3. Fase 4 — "Menos tecleo, más automático" — ✅ COMPLETADA

**Estado (2026-09-07): Fase 4 COMPLETADA.** Épicas E, F y G cerradas. **Fase 5 APARCADA** por
decisión del grupo (coste/beneficio no compensa para un grupo pequeño) — solo se hicieron los
puntos baratos (vista "Tu semana", retención). Ver §4.

### Épica E — Campaña de inicio de curso / revisión de datos — ✅
Todo el circuito vive en `MemberChangeRequest` (columna `payload`), `Portal\ChildController` y la
bandeja `Members\ChangeRequestController` (`/revisiones-familias`, permiso `members.manage`, alcance
por rama vía `MemberPolicy@update`, aviso en el dashboard). La familia, desde `Portal/Child`, en un
solo formulario:
- [x] **Datos del scout** (teléfono, email) y **ficha sanitaria** (alergias, intolerancias,
      medicación, observaciones — solo se aplican si el revisor tiene `members.sensitive`).
- [x] **Contacto de la familia** (`Family.contact_phone` / `contact_email`) — se aplica a la
      familia vinculada a la cuenta que envía la revisión.
- [x] **Consentimientos** (RGPD, imagen, salidas periódicas) con su texto legal: al aprobar se
      hace `updateOrCreate` en `consents` con `signed_at` = hoy. Cubre la re-recogida de curso.
- [x] **Renovación de plaza**: "¿continúa el curso {año}?" — si la familia responde **No**, al
      aprobar el miembro pasa a `active = false`; si responde Sí, no se toca nada.
- Secretaría ve el "antes → después" de todo (incluidos consentimientos y renovación) y aprueba o
  rechaza con motivo.
- **Hecho:** el inicio de curso es "que las familias revisen y respondan desde el portal, y
  secretaría aprueba", sin reimportar CSV.

### Épica F — Del plan de rama al calendario — ✅
- [x] **Lista de material agregada** de una salida: `App\Services\Events\EventMaterialList` suma las
      cantidades de `ActivityMaterial` de todas las actividades del evento (agrupando por ítem de
      inventario o, si no, por nombre) y cruza con la disponibilidad en las fechas del evento.
      PDF `events.pdf.materials` (`/eventos/{event}/pdf/material`), botón en `Events/Show` y enlace
      desde el paso "Material" del panel "Preparar salida". El **dossier** incluye ahora una tabla
      "Material total del evento".
- [x] **Generar los eventos del trimestre** desde el plan de rama: `BranchPlanScheduleController@store`
      (`POST /branch-plans/{plan}/reuniones`) crea en bloque las reuniones semanales de un tramo de
      fechas (día y hora fijos) para la rama del plan; no duplica reuniones ya existentes ese día y
      respeta `skip_dates`. Modal en `BranchPlans/Show` con vista previa de las fechas antes de crear.
- [x] ~~Ficha de sesión imprimible del día (guion)~~ — **retirada el 2026-09-07**: la ficha de salida
      MSC cubre lo mismo con el formato oficial, así que el guion no aportaba nada. El dossier sigue
      siendo la versión completa.
- [x] **Formato oficial MSC del plan de rama y de la salida** (2026-09-07): los objetivos se rellenan
      con el catálogo de la delegación (Ámbito → Línea → Contenido, `App\Support\MscPlanCatalog`),
      el diagnóstico "¿cómo estamos?", el objetivo en verbo + complemento y la evaluación
      "¿cómo ha salido?". Dos impresos nuevos: **hoja de programación trimestral**
      (`branch-plans.pdf.term`) y **ficha de salida de la delegación** (`events.pdf.msc-outing`),
      con la rejilla ESTRUCTURA por día y franja horaria.

### Épica G — Cierre de curso — ✅
- [x] **Exportación del censo MSC** (`MemberController@censusMsc`, `/members/censo-msc`): una fila
      por persona con sección, cargo (educando/scouter), datos identificativos (`dni`/`sex`/`address`
      nuevos en `members`), contacto y, para el kraal, titulación y certificado de delitos sexuales.
      Botón en `Members/Index`. *(Pendiente de ajustar el orden/nombres de columnas al fichero exacto
      de la federación cuando lo faciliten.)*
- [x] **Memoria del curso**: `AnnualReportService` + `Reports\AnnualReportController` (`/memoria` y
      `/memoria/pdf`): por curso escolar, progreso de objetivos por plan de rama (total/logrados/%
      y por ámbito de desarrollo), eventos realizados (globales y por rama), asistencia media a
      reuniones y censo actual. Página con selector de curso + PDF. Alcance por rama.
- [x] **Presupuesto vs. real**: `Budget::summary()` (previsto/real/desvío del balance). El informe
      económico (`FinanceReportService`/`Finance/Report.vue`) lista cada presupuesto de evento del
      periodo con su desvío y lo incluye en el CSV. La ficha del presupuesto muestra el desvío.

---

## 4. Fase 5 / backlog — APARCADA (decisión del grupo, 2026-09-07)

**Decisión:** no se abordan estas mejoras por ahora. El grupo es pequeño y sin ánimo de lucro; el
coste (todas son 800 €+ y varias > 2.000 €, o semanas de trabajo si lo hace el propio grupo) no
compensa el ahorro de tiempo que darían. La plataforma **ya cubre los trabajos recurrentes del
curso** (Fases 3 y 4 completas). Retomar un punto concreto solo si aparece una necesidad real y
recurrente que lo justifique; el análisis de coste/beneficio de cada uno está más abajo para no
rehacerlo.

**Ya hecho de esta fase** (eran baratos o ya existían):
- [x] **Vista "Tu semana"** en el panel de inicio: rejilla lun–dom con los eventos de las ramas del
      usuario y las tareas de kraal que vencen esa semana. `DashboardController@thisWeek`.
- [x] **Firma con trazo/imagen** — ya estaba en `Public/Signature` y `Public/Enrollment`
      ("Paso 3: Firma Táctil"). El punto figuraba por despiste.
- [x] **Métricas de retención / bajas** — columna `left_at` en `members` + bloque "Altas y bajas
      del curso" en la memoria (`AnnualReportService@retention`).

**Aparcado, con su coste/beneficio ya analizado (2026-09-07):**

| Mejora | Problema que resuelve | Frecuencia del dolor | Esfuerzo | Coste (35–50 €/h) | Veredicto |
|--------|-----------------------|----------------------|----------|-------------------|-----------|
| PWA instalable + notificaciones push | Un aviso llega aunque no abras la app/correo | Semanal | 5–7 días | 1.050–2.100 € | Mejor valor/esfuerzo, pero solapa con WhatsApp |
| Asistencia offline | Pasar lista en campamento sin cobertura | Mensual (solo salidas sin wifi) | 7–11 días | 1.470–3.300 € | Alternativa de coste 0: papel + teclear al volver |
| Calendario bidireccional (Google 2 vías) | Crear eventos desde tu Google y que lleguen a la plataforma | Rara (los eventos se meten 1 vez en la plataforma) | 8–12 días | 1.680–3.600 € | Descartar — el iCal de solo lectura ya cubre el 90 % |
| Multi-grupo / multi-tenant | Ceder la plataforma a otros grupos | Nunca para este grupo | 18–30 días | 3.780–9.000 € | Solo con compromiso firme de 2+ grupos; riesgo RGPD alto |

**Extras baratos** que quedaron sueltos, por si alguna vez entran: recibo individual en PDF para la
familia (~½ día), hoja médica en pantalla para el móvil sin PDF (~½ día), circular del evento con
diseño de marca (~1,5–2 días, solo si se decide usarla).

---

## 5. Reglas para ejecutar cualquier épica

1. **Antes de codar**: leer [ESTADO.md](ESTADO.md) del módulo afectado y [CONVENTIONS.md](../CONVENTIONS.md).
2. Reutilizar componentes de `Components/Shared/*` y servicios existentes (`DriveServiceInterface`,
   `SignatureService`, `InvoicePdfService`, `ChargeAssignmentService`, `FinanceReportService`).
3. Cambios de esquema → **migración aditiva** + fila en [CHANGES.md](../CHANGES.md). Nunca editar
   migraciones del núcleo.
4. Toda acción nueva pasa por una **Policy**. Incluir test de autorización cruzada (responsable de
   otra rama → 403).
5. Todo texto visible en **español**; código/BD en inglés.
6. **Al terminar**: mover la línea en [ESTADO.md](ESTADO.md) a ✅, marcar `[x]` aquí, `vendor/bin/pint --dirty`, tests del módulo en verde.

---

## 6. Cómo sabremos que la Fase 3 funcionó — ✅ cumplido

- Preparar una salida: **1 pantalla** (`Events/Show` → panel "Preparar salida") con el estado de
  los 8 pasos (hoy ya no son 6–8 pantallas sueltas). ✅
- Llevar al campamento la **hoja médica** de todos los inscritos: **1 clic** (`events.pdf.attendees`,
  enlazado desde el panel). ✅
- Tras la salida, marcar en efectivo a los que pagaron: **1 acción en bloque** (`bulkMarkPaid`). ✅
- Pasar lista: el responsable entra y ya está en su última rama y en la fecha de hoy. ✅
- El kraal ve sus tareas de preparación en el panel de inicio. ✅
