# PROMPT MAESTRO — Sistema de Control de Bienes Informáticos (InventarioTI)

> Copia todo este contenido como primer mensaje al iniciar el proyecto. Al retomar en una sesión o modelo nuevo, vuelve a pegarlo junto con el archivo ESTADO.md del repositorio.

## 1. ROL Y CONTEXTO

Actúa como arquitecto y desarrollador senior full-stack especializado en Laravel + Livewire. Vas a construir desde cero un sistema web empresarial de control de bienes informáticos, inspirado en GLPI pero SOLO en sus módulos de inventario, activos, soporte por problemas, gestión, herramientas y administración. NO incluye tickets de mesa de ayuda ni gestión de proyectos.

Problema que resuelve: la empresa necesita controlar sus bienes informáticos (equipos de cómputo, laptops, monitores, impresoras, periféricos, consumibles, licencias de software), asignarlos a empleados, conservar el histórico completo de asignaciones y estado de operación de cada bien, generar cartas responsivas, y registrar problemas/incidencias ligadas a los equipos.

## 2. STACK TECNOLÓGICO (OBLIGATORIO)

- Backend: PHP 8.2+, Laravel (última versión estable), MySQL 8
- Frontend: Livewire 3, Tailwind CSS, Flowbite, Alpine.js
- Tablas: rappasoft/laravel-livewire-tables v3 (verificar compatibilidad de versión antes de instalar)
- Roles y permisos: spatie/laravel-permission
- Auditoría: spatie/laravel-activitylog
- PDF (cartas responsivas y reportes): barryvdh/laravel-dompdf
- Entorno de desarrollo: XAMPP (Windows). Producción: Ubuntu Server + Apache + MySQL, despliegue con usuario `deploy`
- Idioma de toda la interfaz: español. Código, tablas y variables: inglés

## 3. REGLAS DE TRABAJO (LEER ANTES DE CADA FASE)

1. Trabaja SOLO una fase por vez. Al terminar una fase, entrega un resumen de lo hecho, cómo probarlo, y detente. No avances a la siguiente fase sin mi confirmación.
2. Al finalizar cada fase, actualiza el archivo `ESTADO.md` en la raíz del repo con: fases completadas, decisiones tomadas, estructura actual, pendientes. Este archivo es la memoria del proyecto entre sesiones.
3. Patrón de UI para CRUDs: NO navegar entre vistas para crear/editar. Alta y edición se hacen en un panel lateral deslizante (slide-over) o modal, con Livewire. Al guardar, la tabla se refresca sin recargar la página. Crea UN componente base reutilizable para este patrón y úsalo en todos los módulos.
4. Los catálogos (marcas, tipos, ubicaciones, estados, etc.) deben poder darse de alta "en línea" desde cualquier formulario que los use (botón + junto al select, abre modal), como en GLPI.
5. Toda entidad importante usa SoftDeletes y queda auditada con activitylog (quién, qué, cuándo).
6. Validación siempre en el servidor (Form Requests o reglas Livewire), mensajes en español.
7. Autorización: cada acción protegida por permisos de Spatie; los menús solo muestran lo que el rol permite.
8. Seeders: catálogos base + roles/permisos + usuario admin + datos demo realistas para poder probar cada fase.
9. Migraciones con claves foráneas correctas, índices y comentarios. Nada de columnas sueltas sin relación.
10. Código limpio: componentes Livewire por módulo, servicios para lógica de negocio, sin lógica en las vistas.
11. Si algo del requerimiento es ambiguo, pregunta antes de asumir.
12. Antes de dar una fase por terminada: ejecuta las migraciones y seeders desde cero (migrate:fresh --seed), verifica que no haya errores y lista los comandos/pruebas para que yo valide.

## 4. MODELO DE DATOS (BASE MÍNIMA — REFINAR EN FASE 1)

- users: cuentas de acceso al sistema (con roles Spatie)
- employees: empleados de la empresa (separado de users; un empleado puede no tener cuenta). Datos: nombre, número de empleado, departamento, ubicación, correo, teléfono, estado
- employee_accounts: cuentas de acceso corporativas del empleado (correo, dominio, VPN, sistemas), con estado
- Catálogos: asset_types, manufacturers, asset_models, locations, departments, asset_statuses (operativo, en reparación, baja, en resguardo...), suppliers, license_types, problem_categories
- assets: tabla única de bienes. Campos: etiqueta/inventario interno, nombre, tipo, modelo (→ marca), número de serie, estado, ubicación, proveedor, fecha compra, costo, garantía hasta, notas, specs (JSON para campos por tipo: CPU, RAM, disco, SO...), imágenes
- assignments: asignaciones activo ↔ empleado. Fecha de entrega, fecha de devolución, estado al entregar/devolver, observaciones, usuario que registró. El histórico de un activo son todas sus assignments
- responsive_letters: cartas responsivas generadas (folio, empleado, activos incluidos, fecha, PDF generado, estado de firma)
- consumables + consumable_movements: consumibles con stock, entradas y salidas (a quién se entregó)
- licenses + license_assignments: licencias de software (software, tipo, claves, asientos totales, expiración, proveedor, costo) y su asignación a equipos o empleados; alerta de asientos excedidos y expiración
- problems: problemas de soporte ligados a activos (título, descripción, categoría, activo, costo, estado, prioridad, fechas), + problem_notes; histórico vía activitylog
- reminders: recordatorios con fecha inicio/fin, visibilidad
- kb_categories + kb_articles: base de conocimientos con editor de texto enriquecido
- attachments: adjuntos polimórficos (imágenes/archivos para activos, problemas, licencias...)
- settings: configuración de la plataforma (nombre empresa, logo, folios, formato de carta responsiva)

## 5. FASES DE DESARROLLO

### FASE 0 — Fundaciones del proyecto

Ya existe un proyecto base en esta carpeta con Laravel, Tailwind, Livewire y parte de las dependencias instaladas. NO crees un proyecto nuevo. En la Fase 0, primero audita lo existente: versiones de Laravel/Livewire/Tailwind y paquetes instalados (composer.json, package.json), estructura, autenticación y layout actual. Reporta qué sirve, qué falta y qué conviene actualizar antes de continuar, y solo instala lo que falte del stack.

### FASE 1 — Base de datos completa

Todas las migraciones, modelos Eloquent con relaciones, factories y seeders (catálogos, roles/permisos, admin, datos demo). Documentar el diagrama entidad-relación en `docs/DER.md` (mermaid).
Criterio: `php artisan migrate:fresh --seed` sin errores; tinker muestra relaciones funcionando.

### FASE 2 — Autenticación, roles y permisos

Login (Breeze o Fortify con Livewire), recuperación de contraseña, perfiles de usuario. Roles base: Super Admin, Administrador de Inventario, Técnico, Consulta. Matriz de permisos por módulo (ver, crear, editar, eliminar, asignar, reportes). Middleware y directivas en menús/vistas.
Criterio: usuarios demo por rol; cada rol ve solo lo que le corresponde.

### FASE 3 — Componente CRUD base + Catálogos

Componente Livewire reutilizable: tabla (rappasoft) + slide-over/modal de alta/edición + confirmación de borrado + refresco sin recargar. Implementar con él TODOS los catálogos. Alta en línea de catálogos desde selects (botón +).
Criterio: todos los catálogos operables sin navegar entre vistas.

### FASE 4 — Módulo de Activos

Listados por tipo (computadoras, monitores, impresoras, periféricos, etc.) con filtros, búsqueda y exportación. Alta/edición con slide-over, campos specs dinámicos según tipo, carga de imágenes. Vista de DETALLE de activo con pestañas: Información, Especificaciones, Asignaciones (histórico completo: por qué empleados ha pasado), Problemas relacionados, Licencias instaladas, Adjuntos, Histórico de cambios (activitylog). Acciones: cambiar estado, dar de baja.
Criterio: trazabilidad completa de un equipo demo visible en su detalle.

### FASE 5 — Asignaciones y cartas responsivas

Flujo de asignación: elegir empleado, uno o varios activos, fecha, estado y observaciones → genera asignación y actualiza estado del activo. Flujo de devolución con estado de retorno. Generación de carta responsiva en PDF (folio consecutivo, datos de empresa desde settings, empleado, tabla de bienes con series, firmas), descargable y guardada en el expediente del empleado y del activo. Reimpresión y anulación.
Criterio: asignar y devolver un equipo genera PDF correcto y el histórico se refleja en activo y empleado.

### FASE 6 — Consumibles y Licencias

Consumibles: stock, mínimos, entradas/salidas con destinatario, alertas de stock bajo. Licencias: alta con software, claves, asientos, expiración; asignación a equipos/empleados; control de asientos disponibles; alertas de expiración próxima.
Criterio: no se puede exceder asientos; dashboard de alertas muestra vencimientos.

### FASE 7 — Soporte (Problemas)

CRUD de problemas ligados a activos: categoría, prioridad, estado (nuevo, en curso, resuelto, cerrado), costos de reparación, notas con editor enriquecido, adjuntos. El detalle del activo muestra sus problemas; el problema muestra su histórico de cambios.
Criterio: ciclo completo de un problema y costo acumulado por activo visible.

### FASE 8 — Gestión y Herramientas

Proveedores (CRUD completo con datos de contacto). Recordatorios (con fechas y visibilidad). Base de conocimientos (categorías + artículos con editor enriquecido, búsqueda).
Criterio: módulos operando con el patrón CRUD base.

### FASE 9 — Dashboard y Reportes

Dashboard: tarjetas de conteo por tipo de activo (como GLPI), gráficas (activos por estado, por ubicación, por fabricante), alertas (licencias por vencer, stock bajo, garantías por vencer), últimos movimientos. Reportes: inventario general filtrable, activos por empleado/ubicación/estado, histórico de asignaciones, costos de reparación por equipo, licencias; exportación a PDF y Excel; reimpresión de cartas responsivas.
Criterio: cada reporte exporta correctamente con datos demo.

### FASE 10 — Administración, configuración y calidad

Gestión de usuarios del sistema y empleados (detalle de empleado con pestañas: datos, cuentas de acceso, activos asignados, cartas responsivas, histórico). Configuración de plataforma: datos de empresa, logo, formato de folios, textos de carta responsiva. Revisión de seguridad (autorización en todos los endpoints, validaciones), pruebas automatizadas de los flujos críticos (asignación, devolución, permisos), corrección de bugs.
Criterio: suite de pruebas en verde; checklist de seguridad cumplido.

### FASE 11 — Despliegue a producción

Documento `docs/DESPLIEGUE.md` con requisitos del servidor (Ubuntu, Apache, PHP y extensiones, MySQL, Composer, Node). Script `deploy.sh` idempotente para el usuario `deploy`: clonar/actualizar, composer install --no-dev, npm build, migraciones, storage:link, permisos de carpetas, cachés (config/route/view), reinicio de servicios. VirtualHost de Apache con HTTPS. Estrategia de respaldo de BD y storage (cron). Checklist de puesta en marcha y rollback.
Criterio: instalación reproducible en servidor limpio siguiendo solo el documento.

## 6. INICIO

Confirma que entendiste el proyecto, resume el plan en tus palabras, hazme las preguntas que tengas y comienza con la FASE 0.
