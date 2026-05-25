# Esquema de Base de Datos SQL (Tentativo) - SGD-CAS

Este diagrama representa la transición del modelo de persistencia JSON actual a una estructura relacional SQL robusta, incorporando los 19 campos técnicos estandarizados y las relaciones de flujo de préstamo.

```mermaid
erDiagram
    USUARIOS ||--o{ PRESTAMOS : solicita
    USUARIOS ||--o{ AUDITORIA : genera
    USUARIOS ||--o{ ASIGNACIONES : "tiene asignado"
    EXPEDIENTES ||--o{ PRESTAMOS : "se presta"
    EXPEDIENTES ||--o{ ASIGNACIONES : "es asignado a"
    PRESTAMOS ||--|| DEVOLUCIONES : "se cierra con"

    USUARIOS {
        int id PK
        string nombre
        string email
        string password
        string rol "Administrador / Jefe de Línea / Usuario"
        datetime created_at
    }

    ASIGNACIONES {
        int id PK
        int expediente_id FK
        int usuario_id FK
        string asignado_por
        datetime fecha_asignacion
    }

    EXPEDIENTES {
        int id PK
        string no_orden "No. de Orden"
        string codigo "Código"
        string titulo "Nombre Serie / Subserie"
        date fecha_inicial "Fecha Inicial"
        date fecha_final "Fecha Final"
        string caja "U.C. Caja"
        string carpeta "U.C. Carpeta"
        string libro "U.C. Libro"
        string otro_anexo "U.C. Otro"
        int folios "No. Folios"
        int tomos "Tomo"
        string soporte "Soporte (Papel/DVD)"
        string frecuencia_consulta "Alta/Media/Baja"
        string estado "disponible/prestado"
        string ubicacion_fisica "Ubicación (Estante)"
        string expediente_cita "Expediente-CITA"
        string numero_expediente "Expediente (ID)"
        string interesado "Interesado"
        string municipio "Municipio"
        datetime updated_at
    }

    PRESTAMOS {
        int id PK
        int expediente_id FK
        int usuario_solicitante_id FK
        datetime fecha_solicitud
        datetime fecha_prestamo
        string admin_aprueba
        string tipo_vinculacion
        string linea_expediente
        string motivo_consulta
        text observaciones
        string estado "pendiente_prestamo/entregado/devuelto"
    }

    DEVOLUCIONES {
        int id PK
        int prestamo_id FK
        string numero_expediente
        datetime fecha_devolucion
        string nombre_devuelve
        string tramite_realizado
        string numero_acto
        int tomos_entregados
        int folios_recibidos
        int folios_anexos
        string estado_fisico
        string usuario_recibe_archivo
        text observaciones
    }

    AUDITORIA {
        int id PK
        string usuario
        string accion
        string tabla
        int registro_id
        text detalles
        datetime fecha
        string ip
    }
```

## Notas Técnicas del Modelo
*   **Identificadores**: Se recomienda usar `numero_expediente` como índice único (`UNIQUE`) adicional al `id` autoincremental.
*   **Campos de Auditoría**: La tabla `AUDITORIA` es vital para el cumplimiento normativo de archivo, rastreando cada cambio en los 19 metadatos.
*   **Flujo de Préstamo**: La separación entre `PRESTAMOS` y `DEVOLUCIONES` asegura que los datos de foliación final reportados por el usuario no sobreescriban los originales de forma permanente sin pasar por la tabla de control.
