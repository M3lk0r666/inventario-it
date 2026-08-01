# ESTADO DEL PROYECTO — Inventario TI

> Memoria del proyecto entre sesiones. Actualizar al cierre de cada fase.

## FASE 6 — resumen (consumibles y licencias)

**Consumibles** (`/admin/consumibles`): tabla con métricas (total, stock bajo, existencia total), filtros por ubicación y existencia (bajo/suficiente), chip verde/rojo según mínimo. Alta/edición slide-over (existencia inicial genera 1er movimiento). Detalle con **kardex** (entradas/salidas paginadas) y modal para registrar movimiento: salida con destinatario (searchable-select) que **no puede exceder el stock** (lock + transacción); entrada con costo unitario. Eventos: `open-consumable-form`, `confirm-consumable-delete`, `consumable-saved`.

**Licencias** (`/admin/licencias`, sigue la maqueta): métricas (total, por vencer 60d, vencidas, asientos usados/totales), tabla con **barra de asientos** (color según ocupación) y expiración con chip (vencida/por vencer), filtros tipo y vigencia. Alta/edición con validación de que los asientos no bajen de los ya usados. Detalle con métricas de asientos y **asignación a equipo o empleado** (toggle + searchable-select), que **no permite exceder asientos** ni duplicar destinatario activo (lock + transacción); liberación de asientos. Botón "Asignar" se deshabilita como "Agotada" sin asientos. Eventos: `open-license-form`, `open-license-assign`, `confirm-license-delete`, `license-saved`.

**Permisos nuevos** (RoleAndPermissionSeeder): `licenses.assign`, `consumables.move`, y se amplió al Técnico `consumables.edit/move` y `licenses.assign`. **Requiere re-sembrar:** `php artisan db:seed --class=RoleAndPermissionSeeder`.

### Ajuste Fase 6 — renovación de licencias (feedback de Alberto, 2026-07-21)
Distinción entre **expiración** (deja de funcionar) y **fecha de renovación** (límite para renovar antes). Migración `..._000300_add_renewal_to_licenses` (renewal_date, alerts_enabled, alert_days_before=30). En `License`: `renewalStatus()` (none/ok/upcoming/overdue), `needsRenewalAlert()`, scope `needingRenewal()`. Form con fecha de renovación + config de alerta (activar + días antes). Tabla con columna "Renovación" (chip por estado) y filtro "Con alerta activa". Índice con métrica "Renovaciones por atender". Detalle: **banner de alerta** (próxima/vencida) + acción **"Renovar"** (modal que sugiere +1 año, actualiza fechas, reactiva alerta y registra en activitylog). **Portal listo; correo + comando programado (cron) van en el motor de alertas de la Fase 9** (también cubrirá garantías por vencer y stock bajo). **Requiere `php artisan migrate`.**

## FASE 7 — resumen (soporte / problemas)

- **Listado** `/admin/problemas`: métricas (total, abiertos, críticos abiertos, costo acumulado de reparaciones), tabla rappasoft con filtros por estado/prioridad/categoría y "abiertos/resueltos", chips por estado y prioridad, enlaces a activo y a detalle.
- **Alta/edición** (`ProblemForm`, slide-over): título, activo (searchable-select), descripción, categoría (con "+"), prioridad, estado, costo de reparación, fecha de reporte, responsable (técnico) y adjuntos. `resolved_at`/`closed_at` se **sellan automáticamente** según el estado.
- **Detalle** (`ProblemDetail`): encabezado con chips y **cambios rápidos de estado**; columna principal con descripción + pestañas Seguimiento (línea de tiempo de notas con alta/borrado), Adjuntos e Histórico (activitylog); panel lateral con todos los datos (costo, fechas, responsable).
- **Integración**: botón "Reportar problema" habilitado en el detalle del activo (pestaña Problemas) — llega con el activo preseleccionado; los títulos enlazan al detalle del problema. Sidebar "Problemas" conectado.
- Eventos: `open-problem-form` (acepta `assetId`), `confirm-problem-delete`, `problem-saved`.
- Criterio cumplido: ciclo completo de un problema (nuevo→en curso→resuelto→cerrado) y costo acumulado por activo visible en la ficha del activo.

## FASE 8 — resumen (gestión y herramientas)

- **Proveedores** `/admin/proveedores` (CRUD completo): tabla con búsqueda (nombre/RFC/contacto/correo) y conteo de activos; form slide-over con todos los datos de contacto (RFC, contacto, correo, teléfono, sitio, dirección, notas); detalle con datos + activos suministrados y conteo de licencias; borrado bloqueado si tiene activos/licencias. El "+" de alta rápida (catálogo `proveedores`) sigue funcionando en los formularios de activos/consumibles/licencias.
- **Recordatorios** `/admin/recordatorios` (`RemindersManager`): tarjetas con filtros Próximos/Míos/Todos, rango de fechas y visibilidad privado/público (`scopeVisibleTo`); cada usuario ve públicos + propios y solo edita/elimina los suyos.
- **Base de conocimientos** `/admin/base-conocimientos` (`KbManager`): navegación por categorías (con conteo), búsqueda en título/cuerpo, lectura de artículo (incrementa vistas), CRUD con **editor enriquecido Trix** (`<x-rich-text>`, Trix por CDN en el layout), slug automático, borradores (no publicados solo visibles para quien edita). Categorías se administran vía catálogo `categorias-kb` (+ alta en línea).
- Sidebar: Proveedores, Recordatorios y Base de conocimientos conectados.

### Ajustes KB (feedback de Alberto, 2026-07-21)
- **Trix ahora es local** (no CDN): paquete npm `trix` importado en `resources/js/app.js` (`import 'trix'` + `import 'trix/dist/trix.css'`), empaquetado por Vite → funciona sin internet. Estilos del editor movidos a `app.css`. (Criterio del proyecto: preferir assets locales por si el servidor queda sin internet.) **Requiere `npm install` + `npm run build`.**
- **Crear/editar artículo en página propia** (estilo GLPI, a todo lo ancho): `KbArticleEdit` en `/base-conocimientos/nuevo` y `/{article}/editar`. `KbManager` quedó solo para lista/lectura. Esto además resolvió el error de `@entangle('data.body')` (el editor ya no vive dentro de un slide-over con estado vacío).
- Componente `<x-rich-text>` robustecido: espera el evento `trix-initialize` antes de `loadHTML` (evita "Cannot read properties of undefined").
- Toasts tras redirección: el layout muestra `session('toast')` al cargar (método `push` en toastManager).

## FASE 9 — resumen (dashboard, reportes, alertas y correo)

- **Dashboard** (`/admin`): tarjetas principales (activos/asignados/disponibles/reparación) + secundarias (empleados, licencias, consumibles, problemas), **panel de alertas** (renovaciones de licencias, garantías por vencer, stock bajo), gráficas de barras (por estado, por ubicación — CSS puro, sin librería para no depender de internet), bento por tipo (GLPI) y últimos movimientos (asignaciones y problemas).
- **Motor de alertas** `App\Services\AlertService`: renovaciones de licencias, licencias por vencer/vencidas, garantías por vencer (60d), stock bajo. Reutilizado por dashboard y correo.
- **Correo O365 configurable** `App\Services\MailConfigurator`: aplica SMTP desde settings (mail_host/port/encryption/username/password/from) en runtime, sin tocar .env. `isReady()`, `alertRecipients()`. **La UI de configuración va en Fase 10** (por ahora se prueba fijando settings por tinker).
- **Digest de alertas por correo**: `App\Mail\AlertDigestMail` + comando `alerts:digest` (programado en `routes/console.php`, `weekdays 08:00`). No envía si el correo no está listo o no hay alertas. **Requiere cron ejecutando el scheduler.**
- **Compartir artículo de KB por correo**: en la lectura, "Compartir por correo" → destinatarios combinables (empleados con correo, **listas de distribución** del catálogo `listas-de-correo`, correos libres) + mensaje. `App\Mail\KbArticleMail`. Auditoría en `kb_article_shares`. Preferir listas O365 (1 correo, O365 reparte). Nuevo catálogo "Listas de correo" (tabla `mailing_lists`).
- **Reportes** (`/admin/reportes`): `ReportService` con 6 reportes (inventario, por empleado, histórico de asignaciones, costos de reparación, licencias, consumibles) con filtros (tipo/estado/ubicación/empleado/estado-problema/fechas) y **exportación CSV y PDF** (paisaje). Permiso `reports.export`.

**Migraciones requeridas:** `php artisan migrate` (mailing_lists, kb_article_shares).
**Config correo (Fase 10 o manual):** settings `mail_enabled=1`, `mail_host=smtp.office365.com`, `mail_port=587`, `mail_encryption=tls`, `mail_username`, `mail_password` (contraseña de aplicación), `mail_from_address`, `alert_recipients`.

## FASE 10 — resumen (administración, configuración y calidad)

- **Configuración** `/admin/configuracion` (`ConfigManager`, permiso settings.view/edit): pestañas Empresa (nombre + subida de logo), Cartas responsivas (prefijo/consecutivo/texto), **Correo SMTP O365** (host/puerto/cifrado/usuario/contraseña de aplicación/remitente/destinatarios de alertas) con **botón "Probar"** (`TestMail`). El correo queda operativo tras guardarlo aquí (alimenta el digest y el compartir de KB).
- **Usuarios del sistema** `/admin/usuarios` (`UsersTable`/`UserForm`): CRUD con rol único (Spatie), reset de contraseña opcional, no permite auto-eliminarse, desvincula del empleado al borrar.
- **Empleados** `/admin/empleados` (`EmployeesTable`/`EmployeeForm`/`EmployeeDetail`): CRUD con vínculo opcional a cuenta de usuario; **ficha con menú lateral** (estilo GLPI): Datos, Cuentas de acceso (CRUD inline de employee_accounts), Activos asignados (histórico + carta), Cartas responsivas, Histórico (activitylog). Borrado bloqueado con bienes asignados.
- **Auditoría** `/admin/auditoria` (`ActivityLog`): bitácora global de activitylog con filtros por módulo y evento.
- **Seguridad**: todas las rutas admin bajo su `permission:*`; componentes Livewire autorizan cada acción; dashboard común a autenticados.
- **Pruebas** (`tests/Feature`): `InventoryTestCase` (siembra catálogos+roles), `PermissionsTest` (acceso por rol), `AssignmentFlowTest` (asignación genera carta+estado, devolución), `StockAndSeatsTest` (no exceder stock ni asientos). Corren en SQLite en memoria (phpunit.xml); fulltext de KB ahora es condicional a MySQL para no romper las pruebas.

**Migraciones:** sin nuevas en Fase 10 (mailing_lists y kb_article_shares fueron de Fase 9).
**Pruebas:** `php artisan test`.

### Ajuste Fase 10 — Roles y permisos editables (feedback de Alberto, 2026-07-22)
Editor de matriz de permisos por rol estilo GLPI (`RoleManager`, `/admin/roles`, permiso `users.edit`): tabla módulos × acciones (ver/crear/editar/eliminar) + sección de permisos especiales, con checkboxes que otorgan/quitan permisos al rol (Spatie). Permite **crear nuevos roles**. El rol Super Admin se muestra bloqueado (tiene todo por `Gate::before`). Acceso: botón "Roles y permisos" en Usuarios + enlace "Ver/editar permisos" en el formulario de usuario al elegir rol. También: número de empleado con consecutivo automático (como activos).

### Ajuste 10.1 — usuarios (feedback de Alberto, 2026-07-22)
1. **Seeder sin contraseña conocida**: `UserSeeder` crea los 4 usuarios base con contraseña aleatoria (no utilizable). Cada contraseña se fija después con **`php artisan user:password <correo>`** (comando interactivo `SetUserPassword`).
2. **Super Admin de arranque protegido**: nueva columna `users.is_protected` (migración ..._000300). El admin@inventario.test queda protegido → no eliminable. Además, el sistema **no permite eliminar al único Super Admin** ni la propia cuenta. En la tabla, cuentas protegidas muestran candado y ocultan el botón de borrar. (Idea: entrar la 1ª vez con ese, crear otro Super Admin de trabajo, y conservar el de contingencia.)
3. **Correo de acceso al crear usuario**: al dar de alta un usuario (con rol) se envía `WelcomeUserMail` con el rol, el usuario y un **enlace para establecer su contraseña** (token de `password.reset`) + link del portal. Checkbox "Enviar correo de acceso" en el alta. Botón **"Reenviar acceso"** en la tabla (nuevo enlace). Requiere correo configurado (Fase 9/10).

**Migración requerida:** `php artisan migrate` (users.is_protected). Tras `migrate:fresh --seed`, fijar contraseñas con `user:password`.

### Aclaración empleados vs usuarios + acceso desde ficha (2026-07-22)
Conceptos: **empleados** = personas como sujetos del inventario (reciben equipo, cartas; la mayoría no entra al sistema); **usuarios** = cuentas para operar el portal (con rol). El campo `employees.user_id` vincula ambos cuando una persona es las dos cosas (no es duplicación, es enlace).
Nuevo flujo: **otorgar/revocar acceso desde la ficha del empleado** (pestaña "Acceso al portal", `EmployeeDetail`): botón crea la cuenta de usuario con rol, la vincula (`user_id`) y envía el correo de bienvenida; "Reenviar acceso" y "Revocar acceso" (elimina la cuenta, conserva el empleado; no revoca cuentas protegidas). Lógica compartida en `App\Services\PortalAccessService` (createUser + sendWelcome con enlace de 24 h). Requiere permisos `users.create`/`users.delete` además de `employees.edit`.

## FASE 11 — resumen (despliegue a producción) — PROYECTO COMPLETO

Documentación y scripts en `docs/DESPLIEGUE.md` + carpeta `deploy/`:
- **`deploy.sh`**: despliegue/actualización idempotente (git pull, composer --no-dev, npm ci+build, migrate --force, storage:link, cachés config/route/view/event, permisos, modo mantenimiento). Vars `BRANCH`, `SKIP_GIT`.
- **`deploy/make-selfsigned-cert.sh`**: certificado **autofirmado local** (HTTPS sin dominio público, CN por nombre o IP, con SAN) → `/etc/ssl/inventario-it/`. (Opción pedida por Alberto.)
- **`deploy/apache-inventario.conf`** (HTTP→HTTPS) y **`apache-inventario-ssl.conf`** (VirtualHost 443 apuntando a `public/`, con Opción A autofirmado / Opción B Let's Encrypt, TLS endurecido y cabeceras de seguridad).
- **`deploy/backup.sh`**: respaldo BD (mysqldump gz) + storage, retención configurable. **`deploy/crontab.example`**: scheduler (`schedule:run` para `alerts:digest`) + respaldo diario. **`deploy/env.production.example`**.
- `docs/DESPLIEGUE.md`: requisitos, BD, primer despliegue, seed + `user:password`, Apache, HTTPS (autofirmado y Let's Encrypt), cron, respaldo/restauración, actualizaciones, **checklist de puesta en marcha** y **rollback**.

> Con esto se completan las 11 fases del proyecto. El sistema es instalable en un servidor limpio siguiendo solo `docs/DESPLIEGUE.md`.

### Extra — página de bienvenida (2026-07-22)
`/` ahora es una **página pública de bienvenida** (`resources/views/welcome.blade.php`, ruta `welcome` en `routes/web.php`): logo/nombre de empresa desde settings, objetivo de la herramienta, tarjetas de los módulos y botón "Acceder al portal" → login (o /admin si ya hay sesión). El panel sigue en `/admin` (protegido). `ExampleTest` actualizado (welcome carga 200).
Aclaración contraseñas: `user:password` es para el **arranque inicial** (aún sin sesión, no puede ser botón); para usuarios existentes ya se establece desde Usuarios → Editar (campo contraseña) y "Reenviar acceso" (enlace de 24 h).

### Seeders separados prod/dev (2026-07-22)
- `SettingSeeder` (config base: company_name, folios, texto de carta, defaults de correo) con `firstOrCreate` (no pisa lo configurado).
- **`ProductionSeeder`**: solo base (Catalog + RoleAndPermission + User + Setting). Correr con `--class=ProductionSeeder` en producción.
- **`DemoSeeder`**: datos de prueba (empleados/activos/asignaciones…); ahora **asegura la base** llamando a `ProductionSeeder` al inicio (idempotente), así se puede correr solo con `--class=DemoSeeder`.
- **`DatabaseSeeder`** (por defecto): en producción → `ProductionSeeder`; fuera → `DemoSeeder`. Se puede elegir explícitamente cualquiera con `--class`.

### Ajuste — folios/cartas por tipo (feedback de Alberto, 2026-07-22)
Configuración → Cartas dividida en dos secciones con **prefijo, folio inicial y texto propios por tipo**:
- **CAB — Carta de Aceptación de Bienes** = tipo `delivery` (empleado recibe). Prefijo default `CAB`, texto de aceptación (1ª persona).
- **CEB — Carta de Entrega de Bienes** = tipo `return` (empleado devuelve/egresa). Prefijo default `CEB`, texto de entrega/custodia.
Settings: `letter_delivery_prefix/start/text`, `letter_return_prefix/start/text` (reemplazan `letter_folio_prefix/next_number/intro_text`). Textos default en `ResponsiveLetterService::DEFAULT_TEXT` (fuente única; usados por SettingSeeder, ConfigManager y PDF). Título del PDF y texto por tipo.
**Folio consecutivo blindado (punto 3):** `nextFolio($type)` DERIVA el número del **máximo folio existente para ese prefijo+año + 1** (incluye eliminados) → siempre consecutivo, nunca sobrescribe ni depende de un contador manual. El "folio inicial" solo aplica cuando aún no hay folios de ese prefijo+año; al guardar en Configuración se **clampea** para no quedar por debajo de lo ya emitido (evita huecos/duplicados). Cambiar de prefijo inicia una secuencia nueva por prefijo.
Mapeo confirmado por Alberto: **CAB = entrada** (colaborador recibe), **CEB = salida** (colaborador entrega los bienes al responsable del área/almacén al egresar). Textos legales redactados acordes en `DEFAULT_TEXT`. Botones de Asignaciones: "Nueva asignación (al colaborador)" y "Recepción (salida del colaborador)"; títulos de modales alineados.
**Marcadores en textos de carta:** los textos admiten `{colaborador}`, `{no_empleado}`, `{puesto}`, `{departamento}`, `{empresa}`, `{fecha}`, `{folio}` (constante `ResponsiveLetterService::PLACEHOLDERS`), reemplazados con los datos reales al generar el PDF (`renderPlaceholders`). Configuración muestra la lista de marcadores disponibles.

### Ajuste deploy.sh — permisos en producción (feedback de Alberto, 2026-07-28)
Problemas reales en el servidor: (1) `deploy.sh` sin bit de ejecución (Windows), (2) `chmod` falla en archivos creados por www-data (logo/livewire-tmp) → necesita `sudo chown` antes, (3) `chmod -R 775 storage` hacía ejecutables los `.gitignore` rastreados → git los marcaba como modificados y bloqueaba el pull.
Correcciones en `deploy.sh`: ejecuta `git config core.fileMode false` (ignora cambios de bits), preflight solo por cambios de contenido, y paso de permisos con `sudo chown -R $DEPLOY_OWNER (default deploy:www-data)` + `find -type d -exec chmod 2775` (setgid) / `-type f -exec chmod 664` (evita ejecutables). Flags `SKIP_PERMS`, `DEPLOY_OWNER`. No correr con `sudo ./deploy.sh` (solo el paso de permisos usa sudo). `docs/DESPLIEGUE.md` §4.2: setup una-sola-vez (chown/setgid, core.fileMode false, `git update-index --chmod=+x deploy.sh` desde dev, sudoers NOPASSWD opcional).

### Ajustes de validación producción (feedback de Alberto, 2026-07-28)
1. **Seeder base sin Modelos ni Proveedores**: `CatalogSeeder` (producción) ya no siembra `asset_models` ni `suppliers` (se crean desde el portal). Los ejemplos se movieron a `DemoSeeder` (los activos demo los necesitan).
2. **Fix 500 en Modelos/Activos**: el catálogo Modelos no validaba su unicidad compuesta (name+manufacturer_id) → el duplicado pegaba en el índice de BD y salía 500. Se agregó `unique_scoped` en `CatalogRegistry` + soporte en `CatalogForm::rules()`, y **red de seguridad `try/catch QueryException`** en `CatalogForm::save()` y `AssetForm::save()` → toast/mensaje amigable en vez de error 500 (activos: mensaje en `data.asset_tag`).
3. **`<x-badge-select>`**: selector de una opción como pastillas/badges (para campos con pocas opciones). Aplicado al **estado del activo** en el alta/edición y en el modal "Cambiar estado".

### 2º día de pruebas producción (feedback de Alberto, 2026-07-29)
1. **Estado de activo: se revierte `<x-badge-select>` a `<select>` nativo** en `asset-form` y `asset-detail` (modal cambiar estado). El badge-select tenía un bug de binding (`@entangle.live` no sincronizaba `data.asset_status_id` → "El campo estado es obligatorio" aunque hubiera opción elegida) y además rompía el diseño general. El componente `badge-select.blade.php` queda en el repo pero sin uso.
2. **Imágenes en editor KB (Trix)**: antes se pegaban como data-URI base64 (línea gris, no persistían). Ahora `<x-rich-text>` maneja `trix-attachment-add` y **sube la imagen** vía `POST admin/base-conocimientos/adjuntos` (`TrixAttachmentController`, permiso kb.create/edit, guarda en `storage/app/public/kb/attachments`) e incrusta un `<img>` con URL real → persiste y se muestra en la lista/lectura. CSS `.trix-content img` responsivo. **Requiere `storage:link` + `npm run build`.**
3. **Branding con logo de empresa**: nuevo componente `<x-company-logo>` (usa `company_logo`/`company_name` de settings, fallback a `<x-application-mark>`). Aplicado en el **topbar** (junto al título) y en un **login rediseñado** de dos paneles (marca a la izquierda con degradado primary, formulario a la derecha, en español).
4. **Catálogo "Categorías de KB" renombrado** a "Categorías de artículos" (label/singular en `CatalogRegistry`).
5. **Nombre del usuario en topbar**: junto al avatar ahora se muestra nombre + rol (además del bloque del dropdown y del sidebar, que ya lo tenían).
6. **Borrar cuenta oculto para Super Admin protegido**: en `profile/show` la sección delete-user-form se oculta si `is_protected` (como en Usuarios no puedes eliminarte a ti mismo).
7. **Sidebar colapsable**: botón en el topbar (sm+) alterna un store Alpine `sidebar.collapsed` (persistido en localStorage); en colapsado el aside pasa a 4rem (solo iconos, con tooltip `title`) y el contenido ajusta su margen. CSS `.sidebar-collapsed` en `app.css`. **Requiere `npm run build`.**
8. **Ficha de empleado — "Cuenta de sistema"**: muestra "Empleado (sin acceso al portal)" o, si tiene usuario, "Empleado con acceso al portal" + chip con su rol.
9. **Ficha de empleado — bienes adicionales**: sección Datos lista los bienes adicionales en poder del empleado (amparados por cartas de entrega no anuladas, descontando los de cartas de recepción; con el valor capturado y cantidad). Computed `getAdditionalItemsProperty` en `EmployeeDetail` (eager-load `responsiveLetters.items.type`).

### 3er día de pruebas producción (feedback de Alberto, 2026-07-29)
1. **Login redirige al dashboard**: `config/fortify.home` cambiado de `/` a `/admin` (antes caía en la página de bienvenida pública). **Requiere `php artisan config:clear`.**
2. **Borrar cuenta oculto para Super Admin**: en `profile/show` la condición se amplió a `! is_protected && ! hasRole('Super Admin')` (el Administrador del Sistema no es `is_protected` pero sí Super Admin, por eso seguía viéndose).
3. **Bienes adicionales con badge verde**: en la ficha de empleado el chip pasó de `chip-neutral` a `chip-success`.
4. **Correo institucional y Extensión Zoom salen de bienes adicionales**: se quitaron del `CatalogSeeder` y se desactivan (`is_active=false`) en BD existente vía migración `..._000100_add_zoom_extension_to_employees` (no se borran para conservar el histórico de cartas; los forms de asignación/recepción ya filtran `is_active`). La **Extensión Zoom** ahora es un dato del empleado: columna `zoom_extension` en `employees`, campo en el alta/edición y en la ficha (Datos). El **correo institucional** ya se captura en el correo del empleado.
5. **Alta de empleado sin "Cuenta de acceso"**: se eliminó el select `user_id` del `EmployeeForm` (causaba confusión con el acceso al portal). El acceso al portal se otorga solo desde el detalle → sección "Acceso al portal" (nota informativa en el formulario). Al editar no se toca el vínculo existente. **Requiere `php artisan migrate` + `config:clear`.**

### 4º día de pruebas producción (feedback de Alberto, 2026-07-30)
1. **Sidebar colapsado: flotante por ícono** (ajustado 2026-07-30): el aside colapsado se queda angosto (solo iconos); al pasar el cursor por un ícono aparece un **flyout flotante** junto a él con la etiqueta y, si tiene submenú (Catálogos), su lista de opciones. Se implementó con Alpine (`sidebarFlyout()` en `admin.blade.php`): `openFly` calcula la posición con `getBoundingClientRect` y el flyout usa `position: fixed` (así no lo corta el `overflow` del contenedor). Se descartó el enfoque anterior de "expandir todo el sidebar al hover".
2. **Botón de colapsar en el pie del sidebar**: se quitó el bloque de usuario (nombre/rol) del pie y del topbar el botón de colapsar; ahora el botón vive al pie del sidebar. El usuario sigue visible en el dropdown del topbar.
3. **Especificaciones (JSON) de tipos de activo**: la ayuda y un `placeholder` explican el formato (arreglo de objetos `{key, label, type}`, type = text|number) con ejemplo. Se capturan aquí los campos dinámicos que luego aparecen al **dar de alta un activo** de ese tipo y en su **pestaña Especificaciones**.
5. **PDFs estandarizados a azul**: la carta responsiva usaba el naranja NETJER `#E87722` (regla superior, borde de títulos, nombre de empresa) → cambiado a `#003d9b` (mismo azul que el reporte). **Requiere `npm run build` + `view:clear`.**

### Distintivo con icono en ventanas (estilo GLPI) (2026-07-31)
`<x-slide-over>` acepta prop opcional `icon` (Remix Icon) y muestra un **badge azul** con ese icono junto al título (encabezado de la ventana). Aplicado a las 12 ventanas: activos, empleados, usuarios, proveedores, consumibles, licencias, problemas, recordatorios, asignación, recepción, compartir KB y catálogos (este último usa el icono propio del catálogo, `$def['icon']`). Para nuevas ventanas basta con `<x-slide-over ... icon="ri-...">`. **Requiere `npm run build` + `view:clear`.**

### Histórico como línea de tiempo (2026-07-31)
Nuevo componente `resources/views/components/activity-timeline.blade.php` (`<x-activity-timeline :activities entity>`): línea vertical con ícono circular azul por evento (add/edit/delete/refresh), tarjeta con autor (en primary) + acción + hora, cuerpo opcional con campos cambiados y nota, y **separadores por fecha** (píldora azul, `isoFormat('D MMM YYYY')` locale es). Todo en azul (default). Se usa en el histórico del **detalle de activo** y del **detalle de empleado** (reemplaza la lista `<ol>` anterior). **Requiere `npm run build` + `view:clear`.**

### Sidebar estilo GLPI — grupos acordeón (2026-07-31)
Reestructura de `layouts/includes/admin/sidebar.blade.php` para resolver la falta de espacio vertical en pantallas chicas:
- Los encabezados con varios items se vuelven **grupos colapsables (acordeón)** con ícono propio; los de un solo item (Dashboard, Problemas, Reportes) quedan como **enlaces sueltos** de nivel superior.
- **Un solo grupo abierto a la vez** (variable Alpine `group` en el `<ul>`): al abrir uno se cierra el anterior. Por defecto abre el grupo de la ruta actual (`$activeGroup`); para arrancar todo cerrado, poner `group: null`.
- **Catálogos** es su propio grupo de nivel superior (icono `ri-list-settings-line`), fuera de Administración; sus hijos son la lista de catálogos (sin icono individual). Administración queda con Usuarios, Configuración y Auditoría.
- En **modo colapsado**, el flotante (`sidebarFlyout`) ahora muestra el nombre del grupo y sus opciones **con ícono**, resaltando al pasar el mouse; Catálogos aparece como subgrupo con su lista. Flotante con `max-h-80vh` + scroll.
- Íconos de grupo: Inventario `ri-stack-line`, Gestión `ri-briefcase-4-line`, Herramientas `ri-tools-line`, Administración `ri-shield-keyhole-line`. **Requiere `npm run build` + `view:clear`.**

### Branding / logos — referencias para manual (2026-07-30)
Puntos donde aparece la marca (logo + nombre de empresa), tomados de settings (`company_logo`, `company_name`, con fallback a `<x-application-mark>` vía componente `<x-company-logo>`):
- **Topbar**: `resources/views/layouts/includes/admin/navigation.blade.php` — logo en línea 16 (`h-10`), nombre en línea 18, subtítulo "Control de bienes informáticos" en línea 19. En el topbar conviene no exceder `h-10` (barra de alto fijo ~64px; un logo mayor empuja el contenido).
- **Página de bienvenida** (`/`): `resources/views/welcome.blade.php` — logo en línea 36 (`h-14`), altura de la barra en línea 33 (`h-20`). Aquí hay más margen para agrandarlo.
- **Login**: `resources/views/auth/login.blade.php` (panel de marca con `<x-company-logo>`).
- **PDFs (cartas/reportes)**: el logo lo resuelve `ResponsiveLetterService::logoPath()` (settings `company_logo` o `company-logo-default.png`).
Tamaños Tailwind: cada unidad de `h-N` = 4px (`h-8`=32px, `h-10`=40px, `h-12`=48px, `h-14`=56px). Cambios de logo/tamaño solo requieren `php artisan view:clear` (no `npm run build`).

### Dominio y ruta de producción (2026-07-30)
Portal servido en **https://inventario-it.netjernetworks.net** (DNS interno, certificado **autofirmado**) desde **/var/www/html/inventario-it**. Se actualizaron: `deploy/apache-inventario.conf` y `apache-inventario-ssl.conf` (ServerName + DocumentRoot/Directory a `/var/www/html/...`), `deploy/make-selfsigned-cert.sh` (CN por defecto = dominio), `deploy/env.production.example` (APP_URL + MAIL_FROM), `deploy/crontab.example` (rutas), `docs/DESPLIEGUE.md` (rutas, ServerName, comandos de cert y prueba). `deploy.sh` y `backup.sh` son agnósticos a la ruta (derivan APP_DIR de su ubicación). En el servidor: fijar `APP_URL` en `.env`, regenerar el certificado con el CN nuevo, copiar los VirtualHost, `a2ensite`, `apache2ctl configtest`, `reload`, y `php artisan config:cache`.

### deploy.sh — estrategia "espejo" (2026-07-28)
El servidor de despliegue tuvo un commit local → `git pull` falló por ramas divergentes. Solución: `deploy.sh` ahora sincroniza con **`git fetch` + `git reset --hard origin/$BRANCH`** (el servidor queda idéntico al remoto; descarta cambios/commits locales). `reset --hard` no toca ignorados (.env, storage/app, vendor, node_modules). Regla: nunca commitear/editar en el servidor. Fix manual una vez: `git fetch origin && git reset --hard origin/main`.
Bit de ejecución: Windows no lo guarda y el `reset --hard` deja `deploy.sh` sin permiso (`Permission denied`). Solución: correr `bash deploy.sh`, o fijarlo en git desde dev: `git update-index --chmod=+x deploy.sh deploy/backup.sh deploy/make-selfsigned-cert.sh`. Documentado en FLUJO-TRABAJO.md §0/§4 y DESPLIEGUE.md §4.2.

## Fases completadas

- **FASE 0 — Fundaciones** (2026-07-18): auditoría del proyecto base y correcciones. Validada por Alberto (incluyó fix de topbar/sidebar con marca duplicada).
- **FASE 1 — Base de datos completa** (2026-07-18): 9 migraciones (25 tablas de negocio), 25 modelos con relaciones, 5 factories, 4 seeders, DER en `docs/DER.md`. Validada por Alberto (migrate:fresh --seed OK, tinker OK, login OK).
- **FASE 2 — Autenticación, roles y permisos** (2026-07-18): middleware Spatie registrados, Gate::before para Super Admin, grupo /admin con sesión Jetstream, auto-registro deshabilitado, /dashboard → /admin, sidebar por secciones filtrado por permisos, rol visible en dropdown y dashboard. Validada por Alberto. Nota: tras sincronizar cambios de vistas conviene `php artisan view:clear` (Blade sirvió una vista compilada vieja durante la validación).

## FASE 2 — resumen

- `bootstrap/app.php`: aliases `role`, `permission`, `role_or_permission`; grupo admin ahora usa `['web','auth:sanctum',jetstream.auth_session,'verified']`.
- `AppServiceProvider::boot()`: `Gate::before` → Super Admin pasa todo (no requiere permisos explícitos).
- `config/fortify.php`: `Features::registration()` deshabilitado (usuarios se gestionarán en Fase 10). Login, reset de contraseña, 2FA y perfiles siguen activos vía Jetstream.
- `routes/web.php`: `/dashboard` es redirect nombrado `dashboard` → `/admin` (compatibilidad con vistas Jetstream).
- Sidebar (`layouts/includes/admin/sidebar.blade.php`): secciones Inventario/Soporte/Gestión/Herramientas/Reportes/Administración; cada item declara `can` y las secciones vacías se ocultan. Items sin módulo aún → `#`; al crear cada módulo solo se agrega `'route' => 'admin.xxx.index'`.
- Patrón para proteger rutas de módulos futuros: `Route::...->middleware('permission:assets.view')` o `->can('assets.view')`.

## FASE 1 — resumen

- **Migraciones** `2026_07_19_0001xx…0009xx`: catálogos (9 tablas), employees + employee_accounts, assets, responsive_letters + assignments, consumables + movements, licenses + license_assignments (morph), problems + notes, reminders + kb, attachments (morph) + settings. Todas con FKs, índices y comentarios; fulltext en kb_articles.
- **Modelos** en `app/Models`: SoftDeletes en entidades de negocio; auditoría vía trait `App\Models\Concerns\TracksActivity` (activitylog: solo campos fillable modificados).
- **Seeders**: `CatalogSeeder` (catálogos realistas MX), `RoleAndPermissionSeeder` (matriz módulo.acción), `UserSeeder` (4 usuarios demo), `DemoSeeder` (15 empleados, 40 activos con specs, asignaciones históricas y activas con cartas CR-2026-xxxx, consumibles con kardex, licencias con asientos, problemas con notas, KB, settings). DemoSeeder NO corre en producción.
- **Usuarios demo** (contraseña `password`): admin@inventario.test (Super Admin), inventario@inventario.test, tecnico@inventario.test, consulta@inventario.test.

### Decisiones Fase 1
- Estado físico al entregar/devolver una asignación = texto libre (`condition_on_assign/return`); `asset_statuses` es el estado operativo del bien (con flag `is_assignable`).
- Claves de licencia en campo `product_key` (text); asientos controlados vía `license_assignments.released_at IS NULL`.
- Estados de problema/prioridad/visibilidad/tipos de cuenta = enums en BD con etiquetas en español como constantes del modelo.
- `settings` = clave-valor con caché (`Setting::get/set`).
- Folio de cartas: prefijo y consecutivo en settings (`letter_folio_prefix`, `letter_next_number`).

## Auditoría Fase 0 — resultado

### Lo que ya sirve
- Laravel **12.64**, PHP ^8.2, MySQL (`inventario_ti`), locale `es` con laravel-lang (lang/es completo).
- Livewire **3.8.2**, rappasoft/laravel-livewire-tables **3.7.3** (compatible con Livewire 3 ✔).
- spatie/laravel-permission **8.3** instalado, migración de permisos ya publicada.
- **Jetstream 5.5** (stack Livewire): login, registro, reset de contraseña, 2FA, perfiles → cubre gran parte de la Fase 2. Teams y API desactivados (correcto).
- Layout admin propio: `layouts/admin.blade.php` + sidebar/navigation/breadcrumbs (Flowbite), rutas en `routes/admin.php` con prefijo `/admin` y nombres `admin.*` (registrado en `bootstrap/app.php`). `/` redirige a `/admin`.
- WireUI **2.6** (preset en tailwind.config) — útil para selects con búsqueda, notificaciones y modales.
- Iconos: Remix Icon y Line Awesome referenciados en el layout (los archivos deben existir en `public/assets/` — **hoy no están**, ver pendientes).

### Lo que faltaba / se corrigió
| Problema | Corrección |
|---|---|
| `@tailwindcss/vite ^4.0.0` conviviendo con Tailwind v3 | Eliminado de package.json. **Decisión: quedarse en Tailwind v3** (compatible con WireUI). |
| Dependencias npm basura (`and`, `build`, `npm`, `run`) | Eliminadas. |
| Flowbite v4 por CDN + clases v4 no compilables (`bg-neutral-primary-soft`, etc.) | CDN removido del layout; se agregó **flowbite ^2.5 vía npm** (plugin + content en tailwind.config, import en app.js); clases del sidebar reescritas a Tailwind estándar. |
| `User` sin trait `HasRoles` de Spatie | Agregado. |
| `APP_NAME=Laravel`, faker en inglés | `APP_NAME="Inventario TI"`, `APP_FAKER_LOCALE=es_MX`. |
| Falta spatie/laravel-activitylog y barryvdh/laravel-dompdf | Pendiente `composer require` (abajo). |

### Comandos pendientes de ejecutar (máquina de Alberto, Windows/XAMPP)
```bash
cd C:\xampp\htdocs\laravel\inventario-it
composer require spatie/laravel-activitylog barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
npm install
npm run build   # o npm run dev
php artisan migrate
```

### Cómo validar la Fase 0
1. Los comandos anteriores terminan sin errores.
2. `php artisan about` muestra Laravel 12.x y los paquetes nuevos en `composer show`.
3. Login en `/login` funciona y `/` redirige a `/admin` (dashboard con sidebar sin clases rotas).
4. `php artisan migrate:fresh --seed` corre sin errores (seeder aún vacío, se llena en Fase 1).

## Decisiones tomadas
- **Referencia de UX: GLPI** (indicación de Alberto, 2026-07-19). Los patrones de interacción y estructura de pantallas se basan en GLPI: detalle con menú vertical izquierdo + contadores, panel de imágenes en la ficha, alta en línea de catálogos ("+" junto a selects), acciones por sección. El estilo visual (colores, tipografía, chips, botones) sigue `docs/DESIGN.md`. Aplicar este criterio en las fases restantes: asignaciones (5), consumibles/licencias (6), problemas (7), herramientas (8), dashboard/reportes (9) y detalle de empleado (10).
- **Frontend: Tailwind v3 + WireUI 2 + Flowbite 2 (npm)**. No migrar a Tailwind 4/Flowbite 4 por incompatibilidad con WireUI (decisión de Alberto, 2026-07-18).
- Autenticación con **Jetstream/Fortify** existente (no se instala Breeze).
- Rutas del sistema bajo prefijo `/admin`, nombres `admin.*`.
- Interfaz en español; código, tablas y variables en inglés.

## Estructura actual relevante
- `routes/admin.php` — rutas del panel (middleware web+auth, prefijo /admin)
- `resources/views/layouts/admin.blade.php` + `layouts/includes/admin/{sidebar,navigation,breadcrumbs}`
- `app/View/Components/AdminLayout.php` (`<x-admin-layout>`)
- Migraciones: framework + permission + 2FA/passkeys. Sin tablas de negocio aún.

## FASE 3 — resumen (patrón CRUD base + catálogos)

Arquitectura del patrón CRUD reutilizable:
- `app/Support/CatalogRegistry.php`: definición central de los 9 catálogos (modelo, columnas, campos con reglas, únicos, slug automático, verificación "en uso" que bloquea eliminación).
- `app/Livewire/Admin/Catalogs/CatalogTable.php`: tabla genérica rappasoft (búsqueda, orden, paginación); se refresca al recibir el evento `catalog-saved`.
- `app/Livewire/Admin/Catalogs/CatalogForm.php` + vista: slide-over de alta/edición con campos dinámicos (text, textarea, checkbox, select, select-catalog, json) + modal de confirmación de borrado. Autoriza `catalogs.create/edit/delete` en servidor.
- `app/Livewire/Shared/CatalogQuickCreate.php`: alta en línea estilo GLPI (botón "+" junto a selects); incluido globalmente en el layout admin; al guardar dispara `quick-created` y el formulario selecciona el nuevo registro.
- `resources/views/components/slide-over.blade.php`: componente Blade del panel deslizante (reutilizable en fases siguientes).
- Toasts globales en layout admin (evento Livewire `toast` {type, message}).
- Página `/admin/catalogos/{catalog?}` (ruta `admin.catalogs.index`, permiso `catalogs.view`). Navegación entre catálogos vía **submenú desplegable en el sidebar** (estilo GLPI, pedido por Alberto); el sidebar soporta items con `children`.
- Eventos del patrón: `open-catalog-form`, `confirm-catalog-delete`, `catalog-saved`, `open-quick-create`, `quick-created`, `toast`.
- tailwind.config: content de rappasoft + safelist de badges dinámicos. lang/es.json: 11 cadenas de rappasoft.

Catálogos operables: departamentos, ubicaciones, fabricantes, tipos-de-activo, estados-de-activo, modelos, tipos-de-licencia, categorias-de-problema, categorias-kb. (Proveedores tiene CRUD completo propio en Fase 8.)

## Design system (integrado 2026-07-19, entre Fase 3 y 4)

Alberto entregó `docs/DESIGN.md` + 4 maquetas HTML (`docs/design/`). Aplicado como tema global:
- **tailwind.config.js**: tokens de color (surface/on-surface/outline/primary #003d9b/primary-container #0052CC/error/success #10B981/alert #E11D48, `canvas` #F4F5F7, `border-soft` #DFE1E6), escala tipográfica (`display-lg`…`mono-sm`, fuente Inter), spacing tokens (container-padding, gutter, table-cell-padding).
- **app.css**: clases utilitarias del sistema — `btn-primary|secondary|ghost|danger|icon`, `card`, `form-label|input|help|error`, `chip` + `chip-success|alert|info|neutral|warning`, `custom-scrollbar`. USAR ESTAS CLASES en todos los módulos nuevos.
- **Layout**: lienzo `bg-canvas`, fuente Inter (bunny.net), topbar blanco con marca azul + subtítulo, sidebar blanco con item activo de acento izquierdo 4px (`border-primary-container bg-surface-container-low text-primary`), bloque de usuario al pie del sidebar, breadcrumbs con icono home.
- **Componentes**: `<x-page-header title description>` con slot `actions` (usar en cada página), `<x-slide-over>`, chips en tablas.
- Iconos: se mantienen **Remix Icon** locales (las maquetas usan Material Symbols; equivalencia visual aceptada para no depender de Google Fonts).
- Botón primario = `bg-primary-container` (#0052CC) según DESIGN.md.

## FASE 4 — resumen (módulo de Activos)

- **Listado** `/admin/activos` (ruta `admin.assets.index`): tarjetas de métricas (total, asignados, disponibles, en reparación), tabla rappasoft con búsqueda (etiqueta/nombre/serie), filtros por tipo/estado/ubicación, chips de estado por color del catálogo, columna "Asignado a", export CSV con filtros aplicados (permiso `assets.export`, con BOM para Excel).
- **Alta/edición** (`AssetForm`, slide-over ancho): campos generales, selects con alta en línea (tipo/estado/ubicación), modelos filtrados por tipo elegido, **specs dinámicos** según `asset_types.spec_fields`, carga múltiple de imágenes → `attachments` (disk public), borrado con confirmación (bloqueado si hay asignación activa).
- **Detalle** `/admin/activos/{id}` (`AssetDetail`): encabezado con chip de estado y acciones; pestañas: Información (con estado de garantía), Especificaciones, Asignaciones (histórico completo con carta y condiciones), Problemas (con costo acumulado), Licencias, Adjuntos, Histórico (activitylog con causer, campos cambiados y notas).
- **Acciones**: cambiar estado (modal con nota opcional registrada en activitylog, permiso `assets.change_status`) y dar de baja (bloqueada con asignación activa; usa estado slug `baja`).
- Eventos: `open-asset-form`, `confirm-asset-delete`, `asset-saved`, `export-assets`.
- Sidebar: "Activos" conectado; items activos también en sub-rutas (fix `Str::beforeLast('.index')`).

### Ajustes post-validación Fase 4 (feedback de Alberto, 2026-07-19)
1. Detalle de activo rediseñado estilo GLPI: **menú vertical izquierdo** con contadores por sección + contenido a la derecha; secciones con acción de agregar donde ya es funcional (Adjuntos: subir archivos ahí mismo; Notas: nueva sección). Botones "agregar" de Asignaciones/Problemas/Licencias aparecen deshabilitados hasta su fase.
2. Búsqueda de la tabla ahora también cubre tipo, estado y ubicación (columnas relacionales `type.name`, `status.name`, `location.name`, `model.name` como searchable en rappasoft).
3. Alta de activo: el número de inventario se **sugiere automáticamente** con el consecutivo del último registrado (conserva prefijo y ceros; verifica unicidad incluyendo eliminados). Editable por el usuario.
4. Nueva tabla polimórfica `notes` (migración 001000) + modelo `Note`; `Asset::deviceNotes()`. Reutilizable para otros módulos. **Requiere `php artisan migrate`.**

## FASE 5 — resumen (asignaciones y cartas responsivas)

- **Servicio** `App\Services\ResponsiveLetterService`: `nextFolio()` ({prefix}-{año}-{0000} desde settings `letter_folio_prefix`/`letter_next_number`, con verificación de unicidad), `generatePdf()` (dompdf, plantilla `resources/views/pdf/responsive-letter.blade.php`, guarda en disk public `responsive_letters/{folio}.pdf`), `ensurePdf()` (regenera si falta el archivo).
- **PDF**: encabezado con logo/nombre de empresa (settings), folio y fecha, datos del empleado, tabla de bienes (etiqueta, tipo, descripción, marca/modelo, serie, estado físico), texto legal (`letter_intro_text`), firmas, y **marca de agua ANULADA** si la carta está cancelada.
- **Asignación** (`AssignmentForm`, slide-over): empleado activo, fecha, estado físico (Bueno/Con detalles/Requiere revisión/Dañado), observaciones, **picker de múltiples activos disponibles** (sin asignación activa + estado asignable, búsqueda por etiqueta/nombre/serie), checkbox de carta responsiva. Transacción: carta + assignments + activos → estado "Asignado" + PDF; abre el PDF en pestaña nueva (evento `open-url`).
- **Devolución** (`ReturnForm`, modal): fecha (≥ entrega), estado físico de retorno, nuevo estado del activo (default "En resguardo"), observaciones anexadas; registra `received_by`. Permiso `assignments.edit`.
- **Listados**: `/admin/asignaciones` (filtros activas/devueltas y por empleado; búsqueda por etiqueta/activo/empleado/folio) y `/admin/cartas` (folio, empleado, bienes, estado; acciones: descargar, reimprimir `responsive_letters.reprint`, marcar firmada `.edit`, anular `.cancel` con regeneración del PDF con marca).
- **Rutas**: `admin.assignments.index`, `admin.letters.index`, `admin.letters.pdf`, `admin.letters.reprint`. Sidebar conectado.
- **Detalle de activo**: botón "Asignar este activo" (si disponible) y botón de devolución en la asignación activa; folios enlazados al PDF.
- Nota técnica rappasoft: con columnas relacionales usar `setAdditionalSelects()` para las FKs del modelo base (aprendido en Fase 4).

### Ajustes post-validación Fase 5 (feedback de Alberto, 2026-07-21)
Basado en el formato real de la empresa (`docs/Formato recepcion activos.docx`).
1. Botón de **Devolución** ahora con texto (antes solo icono).
2. **Bienes adicionales** (llaves, control vehicular, huella, llaves oficina, extensión Zoom, correo institucional, tarjeta digital): nueva tabla `additional_item_types` (catálogo administrable "Bienes adicionales" con flag `requires_value` y `value_label`) + `letter_items` (por carta). Migración `2026_07_21_000100`. **Requiere `migrate` + `db:seed --class=CatalogSeeder`** (o `migrate:fresh --seed`).
3. Formulario de asignación: nueva sección de bienes adicionales con checkbox (y campo de valor si aplica, p.ej. extensión Zoom). Si se marca alguno, se fuerza la carta.
4. **Recepción por empleado (salida)**: nuevo `ReceptionForm` (botón "Recepción (salida)" en Asignaciones) — elige empleado, marca sus activos asignados + estado de retorno + adicionales recibidos, genera **carta tipo `return`**. `responsive_letters.type` = delivery|return; `assignments.return_letter_id`.
5. **PDF rediseñado** al formato de la empresa (`resources/views/pdf/responsive-letter.blade.php`): bloque "Información del Colaborador", tabla Equipo/Accesorio con Descripción/Marca, Serie, Etiqueta, Fecha/Estado; sección "Adicionales"; nota legal; firmas. Dos variantes por `type`: "Carta responsiva de entrega" e "Recepción de activos". Logo por defecto **NETJER Networks** (`storage/app/public/company-logo-default.png`), reemplazable en settings `company_logo` (Fase 10 Configuración).

### 2ª iteración de ajustes Fase 5 (2026-07-21)
6. Fix: la descarga `admin.letters.pdf` ahora **regenera** el PDF en cada descarga (antes servía el archivo cacheado con el diseño viejo). En producción se podrá congelar al firmar.
7. Cartas responsivas: **vista agrupada por empleado** (`LettersByEmployee`, acordeones con búsqueda y filtros por tipo/estado) + toggle "Por empleado / Listado" (el listado plano rappasoft sigue disponible).
8. Filtros nuevos en tabla de activos: "Asignado a" (empleado) y "Asignación" (asignados/sin asignar). Ojo rappasoft: columnas relacionales unen `responsive_letters` que también tiene `employee_id` → calificar `assignments.employee_id`.
9. Fix visual: quitado `overflow-hidden` de las tarjetas con tabla (el popover de Filtros se recortaba con tablas vacías).
10. `<x-searchable-select>`: combobox con búsqueda cliente (Alpine) reutilizable; aplicado al selector de empleado en asignación y recepción (escala con muchos empleados).

### 3ª iteración — firma con evidencia (2026-07-21)
11. **Estado "Firmada" ahora requiere evidencia**: el botón "Marcar firmada" se reemplazó por **"Subir carta firmada"** (`LetterActions`, modal Tailwind): se sube el escaneo/foto (pdf/jpg/png) de la carta firmada → status=signed, `signed_at`, `signed_by`, `signed_document_path`. Enlace "Ver carta firmada" (icono escudo) y ruta `admin.letters.signed`. Migración `..._000200_add_signed_document_to_letters`.
12. **Confirmaciones con modal Tailwind** (no `window.confirm`): anular y firmar viven en el componente compartido `LetterActions` montado en la página; ambas vistas (agrupada y listado) despachan `confirm-cancel-letter` / `sign-letter` y refrescan con el evento `letters-updated`.

**Migración requerida:** `php artisan migrate` (columnas signed_document_path/signed_at/signed_by).

## Pendientes
- Validar Fase 6: correr **`php artisan db:seed --class=RoleAndPermissionSeeder`** (nuevos permisos) + `view:clear`. Ver "Cómo validar Fase 6".
- Buscador global en topbar (maquetas): construir junto con Fase 9.
- **FASE 11** (última): despliegue a producción (docs/DESPLIEGUE.md, deploy.sh, VirtualHost Apache + HTTPS, respaldos, checklist).

## Cómo validar Fase 10
1. `php artisan view:clear`. Como Super Admin:
2. Configuración → Empresa: subir logo y nombre → se refleja en cartas. Cartas: ajustar prefijo/texto. Correo: capturar SMTP O365, guardar, "Probar" a tu correo.
3. Usuarios: crear uno con rol Técnico; iniciar sesión con él y comprobar accesos.
4. Empleados: abrir una ficha → pestañas (agregar una cuenta de acceso, ver activos asignados y cartas). Intentar borrar un empleado con bienes → bloquea.
5. Auditoría: ver la bitácora, filtrar por módulo/evento.
6. `php artisan test` → suite en verde (permisos, asignación/devolución, stock/asientos).

## Cómo validar Fase 9
1. `php artisan migrate` (mailing_lists, kb_article_shares) + `php artisan view:clear`.
2. Dashboard `/admin`: ver tarjetas, alertas (pon una licencia con renovación próxima o un consumible bajo para que aparezcan), gráficas y últimos movimientos.
3. Reportes `/admin/reportes`: cambiar entre reportes, aplicar filtros, exportar **CSV** y **PDF**.
4. Correo (opcional, requiere SMTP): fijar settings por tinker —
   `App\Models\Setting::set('mail_enabled','1'); set('mail_host','smtp.office365.com'); set('mail_port','587'); set('mail_encryption','tls'); set('mail_username','cuenta'); set('mail_password','app-password'); set('mail_from_address','cuenta'); set('alert_recipients','ti@empresa');`
   luego `php artisan alerts:digest --force` para probar el envío.
5. Compartir KB: crea una "Lista de correo" en Catálogos, abre un artículo → "Compartir por correo" → elige lista/empleados/correos → Enviar (requiere correo configurado).

## Cómo validar Fase 8
1. `php artisan view:clear`. Proveedores: crear/editar con datos de contacto; el detalle muestra sus activos; intentar borrar uno con activos → bloquea.
2. Recordatorios: crear uno público y uno privado; cambiar entre Próximos/Míos/Todos; con otro usuario, el público se ve y el privado no.
3. Base de conocimientos: crear un artículo con el editor enriquecido (negritas, listas, enlaces); buscar; leer (sube el contador de vistas); filtrar por categoría; usar "+" para crear categoría al vuelo.

## Cómo validar Fase 7
1. `php artisan view:clear`. Menú "Problemas": ver métricas y tabla con filtros; buscar por título/activo.
2. "Reportar problema": elegir activo, prioridad, estado → guardar (adjunta un archivo opcional). Al ponerlo Resuelto/Cerrado se llenan las fechas.
3. Entrar al detalle: agregar notas de seguimiento (línea de tiempo), cambiar estado con los botones rápidos, ver adjuntos e histórico.
4. Desde el detalle de un activo (pestaña Problemas): "Reportar problema" llega con el activo ya seleccionado; el costo acumulado se refleja arriba.
5. Permisos: `tecnico@` crea/edita; `consulta@` solo ve.

## Cómo validar Fase 6
1. `php artisan db:seed --class=RoleAndPermissionSeeder` + `php artisan view:clear`.
2. Consumibles: crear uno con existencia inicial → aparece en el kardex. Registrar una salida a un empleado; intentar sacar más del stock disponible → debe bloquear. Chip "Stock bajo" cuando existencia ≤ mínimo.
3. Licencias: crear una con N asientos; en el detalle "Asignar asiento" a un equipo y a un empleado; al llenar los asientos el botón queda "Agotada" y no deja exceder; liberar un asiento. Métricas de por vencer/vencidas con datos demo.
4. Con `tecnico@`: mueve consumibles y asigna licencias; `consulta@` solo consulta.

## Cómo validar ajustes Fase 5
1. Migrar + seed catálogo (arriba). En Catálogos aparece "Bienes adicionales" (editable).
2. Nueva asignación: marcar 1-2 activos + algunos bienes adicionales (Extensión Zoom pide número) → genera PDF de entrega con formato de empresa (logo NETJER, tabla equipo + adicionales + firmas).
3. En Asignaciones, botón "Recepción (salida)": elegir empleado con bienes, marcar los que devuelve + adicionales recibidos → genera PDF tipo "Recepción de activos"; los activos cambian de estado y quedan devueltos.
4. En la tabla de Asignaciones, la acción de devolver ahora dice "Devolución" (texto).
5. Cartas responsivas: la columna "Tipo" distingue Entrega/Recepción; filtro por tipo.

## Cómo validar Fase 5
1. `php artisan view:clear` (y `storage:link` si no se ha hecho).
2. Asignaciones → "Nueva asignación": elegir empleado, agregar 2 activos disponibles con el buscador, dejar marcada la carta → guardar. Debe abrir el PDF (folio consecutivo CR-2026-xxxx) y los activos pasan a "Asignado".
3. Verificar el PDF: datos de empresa, empleado, tabla de bienes con series, firmas.
4. En la tabla de asignaciones: devolver uno (fecha, estado físico, nuevo estado) → la asignación queda "Devuelta" y el activo cambia de estado; el histórico se ve en el detalle del activo.
5. Cartas responsivas: descargar, reimprimir, marcar firmada; anular una carta y descargar → debe traer la marca de agua ANULADA.
6. Desde el detalle de un activo disponible: "Asignar este activo" (viene preseleccionado en el formulario).
7. Permisos: `tecnico@` puede crear asignaciones pero no devolver (no tiene assignments.edit); `consulta@` solo ve listados y descarga cartas.

## Cómo validar Fase 4
1. `php artisan view:clear` + `php artisan storage:link` (una vez) + build de assets si no corre `npm run dev`.
2. Menú "Activos": ver métricas y tabla; buscar por etiqueta/serie; filtrar por tipo/estado/ubicación.
3. "Dar de alta activo": elegir tipo Laptop → aparecen specs (CPU/RAM/almacenamiento/SO); probar "+" en tipo/estado/ubicación; subir 1-2 imágenes; guardar → toast y tabla refrescada.
4. Entrar al detalle de un activo demo con historial (p.ej. uno asignado): revisar las 7 pestañas — asignaciones con empleado/carta, problemas con costo acumulado, histórico con cambios.
5. "Cambiar estado" con nota → ver la nota en pestaña Histórico. "Dar de baja" un activo asignado → debe bloquear; uno libre → pasa a Baja.
6. Exportar CSV con un filtro activo → el archivo respeta el filtro.
7. Con `consulta@`: sin botones de alta/edición/borrado ni export; con `tecnico@`: puede editar y cambiar estado, no eliminar.

## Cómo validar Fase 3
1. `php artisan view:clear` y recargar. Entrar como admin → menú "Catálogos".
2. Navegar por los tabs; en cada catálogo: crear con "Nuevo" (slide-over), editar (lápiz) y eliminar (papelera con confirmación). La tabla se refresca sin recargar y aparece un toast.
3. En "Modelos": los selects de Fabricante y Tipo tienen botón "+" que abre el modal de alta en línea; al guardar, el nuevo valor queda seleccionado.
4. Intentar eliminar un catálogo en uso (p.ej. el fabricante Dell) → toast de error "en uso".
5. Con `consulta@inventario.test`: ve las tablas pero sin botones Nuevo/Editar/Eliminar.
6. Buscar y ordenar en las tablas; textos en español.
