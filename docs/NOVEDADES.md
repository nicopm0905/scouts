# NOVEDADES — Fases 3 y 4

Resumen de todo lo implementado en estas sesiones y **dónde verlo**. Todos los flujos tienen datos
de prueba ya sembrados en tu base de datos local (ver el final).

**Logins de prueba** (contraseña `password` en todos):

| Login | Rol | Para probar |
|-------|-----|-------------|
| `admin@grupo.test` | Coordinación (todo) | cualquier pantalla |
| `secretaria@grupo.test` | Secretaría | revisiones de familias, censo, memoria |
| `tesoreria@grupo.test` | Tesorería | cobro en bloque, presupuesto vs real, informe |
| `lobatos@grupo.test` | Responsable (Lobato) | asistencia, preparar salida, plan de rama, "Mis tareas" |
| `familia@grupo.test` | Familia | portal: revisar datos del hijo |

---

## FASE 3 — "Un flujo, no ocho pantallas"

### 1. Asistencia — pasar lista más rápido
**Dónde:** menú **Personas → Asistencia** (`/attendance`) con `lobatos@grupo.test`.
- El modo móvil ya existía (rejilla, un toque por scout, contador en vivo, incidencias).
- **Nuevo:** al entrar sin elegir rama, salta automáticamente a **la última rama que usaste** (se
  recuerda en el navegador). La fecha ya viene puesta en "hoy".
- **Nuevo:** arriba a la derecha, **resumen del trimestre**: nº de reuniones y % de asistencia
  media de la rama (los datos de prueba tienen 7 reuniones sembradas este trimestre).

### 2. Panel "Preparar salida"
**Dónde:** menú **Actividad → Eventos y Salidas** → abre **"Acampada de prueba · Lobato"**
(`/eventos/{id}`) con `lobatos@grupo.test` o `admin@grupo.test`.
- Arriba del todo, panel **"Preparar salida"** con los **8 pasos** para dejar la actividad lista,
  barra de progreso "X/Y listo" y un enlace por paso que te lleva justo a donde se resuelve:
  datos y ramas · semáforo legal de ratios · inscripciones · autorizaciones (x/y) · material ·
  cobro · hoja médica · checklist de documentación. Cada uno en verde / ámbar / gris.

### 3. Hoja médica de asistentes
**Dónde:** en la ficha de la acampada, columna derecha **"Documentos" → "Listado de asistentes
(PDF)"**, o desde el paso "Hoja médica" del panel. Incluye nombre, contacto, alergias/
intolerancias/medicación y estado de pago.

### 4. Reparto de tareas del kraal
**Dónde:** ficha de la acampada → sección **"Checklist y tareas del kraal"**.
- Cada punto de la checklist se puede **asignar a una persona del equipo** y ponerle **fecha límite**
  (en los datos de prueba, "Reservar autobús" y "Comprar material de cocina" están asignadas a
  `lobatos@grupo.test`).
- Esas tareas aparecen en el **panel de inicio** de esa persona: entra con `lobatos@grupo.test` y
  mira la sección **"Mis tareas"**.

### 5. Cobrar en efectivo en bloque
**Dónde:** entra con `tesoreria@grupo.test` → **Tesorería → Cobros** → abre **"Acampada de prueba ·
Lobato"** (`/cobros/{id}`).
- Banda verde arriba: **"Seleccionar los N pendientes"** + método (efectivo por defecto) →
  **"Marcar como pagados (N)"**. Tras una salida, cerrar 20 pagos = 2 clics.
- (Datos de prueba: 8 pendientes, 2 ya pagados.)

---

## FASE 4 — "Menos tecleo, más automático"

### 6. Revisión de datos de la familia + campaña de inicio de curso  *(Épica E)*
**Lado familia:** entra con `familia@grupo.test` → **portal** → pincha en un hijo
(`/portal/scouts/{id}`). Formulario único con:
- ¿Continúa el curso 2027-2028? (Sí / No)
- Teléfono y email del scout
- Teléfono y email de la familia
- Ficha médica (alergias, intolerancias, medicación, observaciones)
- Consentimientos (RGPD, imagen, salidas periódicas) con su texto legal
- Nada se guarda: se envía como propuesta.

**Lado secretaría:** entra con `secretaria@grupo.test` → verás el aviso **"Revisiones de familias"**
en el panel de inicio → **Personas → Revisiones de familias** (`/revisiones-familias`).
- Por cada solicitud, tabla **antes → después** de todo (incluidos consentimientos y renovación).
- **Aprobar** aplica los cambios a la ficha del scout, su familia y sus consentimientos; si la
  familia dijo que **no continúa**, el scout pasa a baja. **Rechazar** con motivo.
- (Datos de prueba: 2 revisiones pendientes de la familia demo — una confirma continuidad, otra
  la rechaza.)

### 7. Del plan de rama al calendario  *(Épica F)*
**Dónde:** entra con `lobatos@grupo.test` → **Actividad → Plan de rama** → abre el plan de Lobato
2026-2027 → botón **"Programar reuniones"**.
- Eliges tramo de fechas, día de la semana, hora y duración → **vista previa** de las fechas → crea
  todas las reuniones semanales de golpe (no duplica las que ya existan).

### 8. Lista de material y guion de sesión  *(Épica F)*
**Dónde:** ficha de la acampada de prueba.
- **"Lista de material (PDF)"** (columna Documentos): suma el material de todas las actividades del
  evento, agrupado, y marca lo que **no llega** en esas fechas según el inventario.
- **"Guion (PDF)"** (junto al título "Cronograma del Evento"): el guion de las actividades del día
  en una hoja (objetivos + desarrollo + material).
- El **dossier** ahora incluye además una tabla "Material total del evento".

### 9. Memoria del curso  *(Épica G)*
**Dónde:** entra con `lobatos@grupo.test` o `admin@grupo.test` → **Actividad → Memoria del curso**
(`/memoria`).
- Selector de curso arriba. Por rama: **progreso de objetivos** (total / logrados / % y por ámbito
  de desarrollo), **eventos realizados**, **asistencia media** a reuniones y **censo actual**.
- Botón **"Descargar PDF"**.
- (Datos de prueba: plan de Lobato 2026-2027 con 6 objetivos en varios estados.)

### 10. Presupuesto vs. real  *(Épica G)*
**Dónde:**
- **Por evento:** con `tesoreria@grupo.test`, ficha de la acampada → botón **"Presupuesto"** →
  banda superior con **balance previsto / real / desvío** (verde si mejor de lo presupuestado).
- **En el informe:** **Tesorería → Informe económico** (`/informe-economico`) → nueva tabla
  **"Presupuesto vs. real (por evento)"** al final; también en el CSV exportado.
- (Datos de prueba: presupuesto de la acampada con 3 partidas y 3 facturas asociadas.)

### 11. Censo oficial MSC  *(Épica G)*
**Dónde:** con `secretaria@grupo.test` → **Personas → Miembros** → botón **"Censo MSC"**.
- CSV con una fila por persona: sección, cargo (educando / scouter), apellidos, nombre, **DNI**,
  **sexo**, fecha de nacimiento, **dirección**, teléfono, email, fecha de alta, activo y —para el
  kraal— titulación y certificado de delitos sexuales.
- Los campos nuevos (**DNI, sexo, dirección**) se editan en la **ficha del miembro** (Crear / Editar).
- (Datos de prueba: DNI y sexo rellenados en ~10 lobatos.)
- *Pendiente:* ajustar el orden/nombres exactos de columnas cuando el grupo facilite el fichero de
  la federación.

---

---

## FASE 5 (mejoras sueltas)

### 12. "Tu semana" en el panel de inicio
**Dónde:** panel de inicio (`/dashboard`) con `lobatos@grupo.test`.
- Rejilla de los **7 días de la semana en curso** (lun–dom) con los eventos de tus ramas en su día,
  el día de hoy resaltado, y un contador de **tareas de kraal que vencen esta semana**.
- Solo aparece si hay algo esa semana. Los datos de prueba siembran 2 reuniones esta semana.

### 13. Altas y bajas del curso (retención)
**Dónde:** **Memoria del curso** (`/memoria`), banda "Altas y bajas del curso" bajo las cifras.
- Cuenta **altas** (por fecha de alta) y **bajas** (por fecha de baja) del periodo, y el balance neto.
- La fecha de baja se guarda **sola**: al desmarcar "activo" en la ficha del miembro o al aprobar
  una renovación con "no continúa". Reactivar al miembro la borra.
- Datos de prueba: 1 alta y 1 baja sembradas en la rama Lobato de este curso.

### 14. Firma con trazo *(ya estaba)*
Las dos firmas públicas ya se hacen dibujando en un recuadro táctil, no con un simple botón:
la de **consentimientos/documentos** (`/publico/firma/{token}`) y la de **autorizaciones de
salida** (enlace público de inscripción → "Paso 3: Firma Táctil", que genera el PDF con la firma).

---

## Datos de prueba

Todo lo anterior tiene datos sembrados por el seeder **`Fase4DemoSeeder`**, ya ejecutado en tu BD
local. Para volver a generarlos (es re-ejecutable, limpia lo suyo antes):

```bash
php artisan db:seed --class=Fase4DemoSeeder
```

Crea, sobre la rama **Lobato** y el curso **2026-2027**:
- "Acampada de prueba · Lobato" (dentro de 3 semanas) con 10 inscritos (6 con autorización),
  checklist con 2 tareas asignadas, 2 actividades con material, cobro de 35 € (8 pendientes),
  presupuesto con 3 partidas y 3 facturas.
- 7 reuniones del trimestre con asistencia ~80 %.
- Plan de rama con 6 objetivos.
- 2 revisiones de datos pendientes enviadas por `familia@grupo.test`.
- DNI/sexo/dirección en ~10 lobatos.

## Estado del proyecto

- **Fase 3** — completada (asistencia, asistente de salida, cobro en bloque, deuda y repaso móvil).
- **Fase 4** — completada (Épicas E, F y G).
- Detalle vivo en [ESTADO.md](ESTADO.md); plan y siguientes pasos en [ROADMAP.md](ROADMAP.md)
  (queda solo la Fase 5 / backlog, bajo demanda).
