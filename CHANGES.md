# CHANGES.md — Migraciones aditivas y cambios al núcleo

Registro de cambios que los agentes de feature hacen sobre el esquema/modelos compartidos.
**No editar migraciones del núcleo.** Cualquier campo nuevo va en una migración aditiva y se
documenta aquí.

| Fecha | Agente | Migración / cambio | Motivo | Afecta a |
|-------|--------|--------------------|--------|----------|
| 2026-07-06 | Orquestador | Núcleo inicial (enums, migraciones §B, modelos, policies, DriveService, CampRatioValidator) | Base compartida | Todos |
| 2026-07-07 | Agente C | Migración aditiva `add_ical_token_to_users_table` (+`ical_token` en fillable de `User`) | Suscripción iCal de solo lectura al calendario de eventos (URL tokenizada por usuario) | Agente C (Calendario) |
| 2026-07-06 | Agente B (Tesorería) | Sin migraciones nuevas. Feature construido sobre el esquema existente (charges, charge_member, invoices, settings): cobros con reparto por rama/miembro y descuento hermanos, facturas + PDF vía DriveService, informe económico CSV, recordatorios (Mailable+Job+`charges:send-reminders`, no cableado en scheduler) y ajustes (`finance.*` keys en Setting) | Agente B |
