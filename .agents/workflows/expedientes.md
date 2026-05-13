---
description: Sistema de gestión documental para control de préstamos, devoluciones y trazabilidad de expedientes de archivo ambiental con validaciones normativas, control de foliación y seguimiento administrativo.
---

# REGLAS GENERALES DEL AGENTE IA

## OBJETIVO DEL AGENTE

El agente IA deberá asistir en el desarrollo, mantenimiento y mejora del sistema de gestión documental de préstamos y devoluciones de expedientes, garantizando estabilidad, trazabilidad, seguridad y cumplimiento normativo.

---

# REGLAS DE DESARROLLO

## CONSERVACIÓN DEL CÓDIGO EXISTENTE

- No eliminar funcionalidades existentes sin autorización explícita.
- No modificar lógica crítica ya funcional si no es necesario.
- Mantener compatibilidad con módulos existentes.
- Evitar refactorizaciones masivas innecesarias.
- Antes de modificar código existente, analizar dependencias y posibles impactos.
- Priorizar cambios incrementales y seguros.
- No romper flujos actuales de préstamo, devolución o trazabilidad.
- Mantener nombres descriptivos y consistentes.

---

# ARQUITECTURA Y ORGANIZACIÓN

## ARQUITECTURA RECOMENDADA

El agente deberá seguir una arquitectura modular basada en separación de responsabilidades:

- Frontend
- Backend
- Servicios
- Validadores
- Persistencia de datos
- Auditoría
- Seguridad

---

## PRINCIPIOS OBLIGATORIOS

- Clean Code
- SOLID
- DRY (Don't Repeat Yourself)
- KISS (Keep It Simple)
- Separation of Concerns
- Modularidad
- Escalabilidad
- Reutilización de componentes

---

# REGLAS PARA BACKEND

## VALIDACIONES

Todas las validaciones deberán ejecutarse tanto:

- Frontend
- Backend

Nunca confiar únicamente en validaciones visuales.

---

## MANEJO DE ERRORES

- Manejar errores mediante try/catch.
- Nunca exponer errores internos al usuario final.
- Registrar logs de errores críticos.
- Generar mensajes claros y controlados.

---

## BASE DE DATOS

- No eliminar datos históricos.
- Priorizar trazabilidad y auditoría.
- Usar relaciones claras y normalizadas.
- Mantener integridad referencial.
- Evitar consultas innecesarias o duplicadas.
- Optimizar consultas pesadas.

---

## SEGURIDAD

- Validar permisos antes de cualquier operación.
- Sanitizar entradas del usuario.
- Evitar inyección SQL.
- No exponer credenciales.
- No almacenar contraseñas en texto plano.
- Registrar acciones críticas de usuarios.

---

# REGLAS PARA FRONTEND

## INTERFAZ

- Mantener diseño institucional y profesional.
- Priorizar formularios claros y simples.
- Mostrar errores de validación de forma amigable.
- Evitar sobrecarga visual.
- Mantener consistencia de colores y componentes.

---

## EXPERIENCIA DE USUARIO

- Minimizar clics innecesarios.
- Mantener navegación intuitiva.
- Mostrar confirmaciones en acciones críticas.
- Implementar estados de carga y mensajes de éxito/error.

---

# REGLAS PARA FORMULARIOS

## CAMPOS OBLIGATORIOS

No permitir envío de formularios incompletos.

Validar obligatoriamente:

- Fechas
- Número de expediente
- Nombre completo
- Tipo de vinculación
- Motivo de consulta
- Trámite realizado

---

## VALIDACIONES AUTOMÁTICAS

El agente deberá validar:

- Máximo 10 préstamos diarios
- Vigencia contractual
- Préstamos vencidos
- Horarios permitidos
- Integridad documental
- Número de tomos y folios

---

# REGLAS DE TRAZABILIDAD

El sistema deberá registrar:

- Fecha y hora
- Usuario responsable
- Acción realizada
- Expediente afectado
- Cambios realizados
- Observaciones

Nunca eliminar registros históricos.

---

# REGLAS DE AUDITORÍA

Todas las acciones críticas deberán quedar registradas:

- Préstamos
- Devoluciones
- Ediciones
- Eliminaciones lógicas
- Cambios administrativos
- Alertas generadas

---

# REGLAS PARA MODIFICACIONES

Antes de implementar cambios el agente deberá:

1. Analizar impacto.
2. Verificar dependencias.
3. Evitar romper compatibilidad.
4. Mantener trazabilidad.
5. Preservar estructura documental.
6. Priorizar estabilidad del sistema.

---

# REGLAS DE DOCUMENTACIÓN

El código deberá:

- Tener comentarios en lógica compleja.
- Mantener nombres claros.
- Documentar endpoints.
- Explicar validaciones importantes.
- Mantener estructura organizada.

---

# REGLAS DE RENDIMIENTO

- Evitar consultas repetitivas.
- Implementar paginación.
- Optimizar carga de expedientes.
- Reducir procesamiento innecesario.
- Mantener tiempos de respuesta eficientes.

---

# REGLAS DE ESCALABILIDAD

El sistema deberá prepararse para:

- Más usuarios concurrentes
- Más expedientes
- Nuevos módulos
- Nuevas líneas documentales
- Integraciones futuras
- Auditorías externas

---

# REGLAS DE COMPORTAMIENTO DEL AGENTE IA

El agente deberá:

- Responder de manera técnica y precisa.
- Priorizar estabilidad sobre cambios agresivos.
- Mantener coherencia arquitectónica.
- Evitar soluciones improvisadas.
- Seguir estándares institucionales.
- Proponer mejoras seguras y mantenibles.

El agente nunca deberá:

- Eliminar código sin análisis.
- Romper funcionalidades existentes.
- Alterar trazabilidad.
- Saltarse validaciones.
- Exponer información sensible.
- Crear lógica duplicada innecesaria.

---

# PRIORIDADES DEL AGENTE

Orden de prioridad:

1. Seguridad
2. Integridad documental
3. Trazabilidad
4. Estabilidad
5. Rendimiento
6. Escalabilidad
7. Experiencia de usuario
8. Optimización estética

---

# TECNOLOGÍAS Y ESTÁNDARES RECOMENDADOS

El agente deberá priorizar:

- Arquitectura modular
- APIs REST organizadas
- Validaciones centralizadas
- Componentes reutilizables
- Manejo de estados limpio
- Logs estructurados
- Auditoría persistente

---

# FILOSOFÍA GENERAL

Toda mejora realizada deberá:

- Mantener estabilidad
- Facilitar mantenimiento
- Garantizar trazabilidad
- Reducir errores humanos
- Fortalecer seguridad documental
- Respetar normativa archivística