# 🏛️ Documentación Técnica - SGD CAS

Este documento contiene la arquitectura y el modelo de datos unificado del Sistema de Gestión Documental.

---

## 1. Mapa de Contexto (Nivel 1)
Muestra la interacción del personal de la CAS con el sistema SGD.

<div align="center">
  <img src="contexto.svg" width="600">
</div>

---

## 2. Diagrama de Contenedores (Nivel 2)
Desglose de la tecnología: Servidor Apache, Lógica PHP MVC y Persistencia JSON.

<div align="center">
  <img src="contenedores.svg" width="800">
</div>

---

## 3. Modelo Entidad-Relación (Datos)
Estructura de las tablas y relaciones lógicas entre los archivos JSON.

<div align="center">
  <img src="modelo_er.svg" width="800">
</div>

---

## 4. Resumen Técnico de Componentes

### 🖥️ Interfaz de Usuario
- **Estilo:** Glassmorphism institucional (Premium).
- **Tecnología:** Vanilla CSS, JavaScript, FontAwesome para iconos.

### 🧠 Capa de Aplicación
- **Rutas:** Despachador basado en Regex.
- **Persistencia:** Motor `JsonDB` que emula el comportamiento de una base de datos SQL sobre archivos de texto.

### 📁 Archivos de Datos (JSON)
- `expedientes.json`: Maestro de documentos y su estado de disponibilidad.
- `prestamos.json`: Registro de solicitudes, entregas y datos técnicos de retorno.
- `usuarios.json`: Gestión de acceso con roles diferenciados (**Administrador** y **Usuario**).
- `auditoria.json`: Registro de cada acción realizada en el sistema para trazabilidad legal.

---

## 5. Flujo de Trabajo (Workflow CAS)

El sistema implementa un modelo de **Responsabilidad Compartida** y **Doble Verificación**:

### 📥 Proceso de Préstamo
1.  **Solicitud:** El **Usuario** diligencia el formulario indicando motivo y línea.
2.  **Verificación:** El **Administrador** revisa los detalles de la solicitud.
3.  **Aprobación:** El **Administrador** confirma la entrega física y el sistema actualiza el expediente a "Prestado".

### 📤 Proceso de Devolución
1.  **Diligenciamiento:** El **Usuario** reporta el estado técnico del retorno (folios, tomos, trámites).
2.  **Recepción:** El **Administrador** verifica físicamente que los folios y tomos coincidan con lo reportado por el usuario.
3.  **Cierre:** El **Administrador** acepta la entrega, liberando el expediente para su próximo uso.

---

## 🔗 Recursos Adicionales

*   🌐 **[Ver Panel Interactivo en Navegador (HTML)](VER_DIAGRAMAS.html)**
*   🖼️ **[Diagrama Contexto (SVG)](contexto.svg)** | **[Diagrama Contenedores (SVG)](contenedores.svg)** | **[Modelo ER (SVG)](modelo_er.svg)**

---
*Documentación Consolidada para CAS - Mayo 2026*
