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

## 3. Fase 4 — "Menos tecleo, más automático" (prioridad MEDIA)

**En curso (2026-09-06):** Épica E arrancada (circuito "familia propone → secretaría aprueba → se
aplica a la ficha"); Épica F: lista de material agregada de una salida ya hecha. Quedan
renovación de plaza / consentimientos (E) y generar el calendario del trimestre desde el plan (F).

### Épica E — Campaña de inicio de curso / revisión de datos
- [x] **Formulario de datos del hijo revisable por la familia** (con aprobación de secretaría antes
      de escribir en `Member`/`HealthRecord`, trazabilidad RGPD): `MemberChangeRequest` (columna
      `payload`), `Portal\ChildController@submitReview` + formulario en `Portal/Child`, bandeja
      `Members\ChangeRequestController` en `/revisiones-familias` (aprobar aplica; los campos
      médicos solo si el revisor tiene `members.sensitive`), aviso en el dashboard. Alcance por
      rama vía `MemberPolicy@update`.
- [ ] **Renovación de plaza**: campaña anual que pide a cada familia confirmar continuidad
      (reutilizará `MemberChangeRequest` o una tabla `enrollment_renewals`).
- [ ] **Re-recogida de consentimientos** en lote al inicio de curso (reaprovecha `Signature`).
- [ ] Permitir en el formulario del portal también los datos de contacto de la **familia**
      (`Family.contact_phone/email`) — hoy solo se aceptan como texto en "Otra información".
- **Hecho cuando:** el inicio de curso es "lanzar campaña y revisar respuestas", no reimportar un CSV.

### Épica F — Del plan de rama al calendario
- [x] **Lista de material agregada** de una salida: `App\Services\Events\EventMaterialList` suma las
      cantidades de `ActivityMaterial` de todas las actividades del evento (agrupando por ítem de
      inventario o, si no, por nombre) y cruza con la disponibilidad en las fechas del evento.
      PDF `events.pdf.materials` (`/eventos/{event}/pdf/material`), botón en `Events/Show` y enlace
      desde el paso "Material" del panel "Preparar salida". El **dossier** incluye ahora una tabla
      "Material total del evento".
- [ ] **Generar los eventos del trimestre** desde el plan de rama / las actividades enlazadas
      (fechas propuestas, tipo, ramas), revisables antes de crear.
- [~] **Ficha de sesión imprimible del día**: el guion sale en el **dossier** (por actividad) y el
      material agregado ya está resuelto; falta, si se pide, una hoja ligera de una sola reunión.

### Épica G — Cierre de curso
- [ ] **Exportación del censo oficial MSC** en el formato que pide la federación (mapear campos de
      `Member`/`Family`/`LeaderProfile`). Ahorra horas a secretaría una vez al año.
- [ ] **Memoria anual**: generador que junta eventos realizados, asistencia media por rama,
      actividades por objetivo del plan y fotos destacadas → PDF.
- [ ] **Presupuesto vs. real**: cerrar el círculo `Budget`/`BudgetItem` ↔ `Invoice`/`Charge` en el
      informe económico y por evento.

---

## 4. Fase 5 / backlog (prioridad BAJA — hacer solo si sobra tiempo o lo pide el grupo)

- [ ] **PWA instalable** + asistencia offline (pasar lista sin cobertura en el bosque, sincroniza al
      volver).
- [ ] Vista "Mi semana" para el responsable en el dashboard (sus reuniones, tareas asignadas,
      cobros de su rama).
- [ ] Integración de calendario bidireccional (hoy iCal es solo lectura).
- [ ] Firma con trazo/imagen además de la confirmación por token.
- [ ] Métricas de retención de scouts / alertas de bajas.
- [ ] App de familia como PWA con notificaciones push.
- [ ] Multi-grupo / multi-tenant (solo si se plantea ceder la plataforma a otros grupos).

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
