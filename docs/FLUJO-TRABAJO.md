# Flujo de trabajo: desarrollo → producción

Ciclo para pasar cambios de tu PC (XAMPP) al servidor de producción (Ubuntu).

```
   PC de desarrollo (XAMPP)                 Repositorio Git                 Servidor de producción (Ubuntu)
   C:\xampp\htdocs\laravel\inventario-it   (GitHub/GitLab/…)                /var/www/html/inventario-it
   ── programas y pruebas ──►  git push  ──►  (remoto)  ──►  git pull / ./deploy.sh  ──►  en línea
```

Usuario de despliegue en el servidor: **`deploy`**.

---

## 0. Configuración una sola vez (primera instalación en el servidor)

Estos pasos evitan los problemas típicos de permisos y de git. Se hacen **una vez**:

```bash
cd /var/www/html/inventario-it

# Propietario y permisos correctos (algunos archivos los crea www-data)
sudo chown -R deploy:www-data .
sudo find . -type d -exec chmod 2775 {} +      # dirs con setgid: heredan grupo www-data
sudo find . -type f -exec chmod 664 {} +
sudo usermod -aG www-data deploy               # deploy dentro del grupo www-data

# Que git ignore cambios de bits de permiso (evita 'M storage/.../.gitignore')
git config core.fileMode false
```

Y **desde tu PC de desarrollo** (una vez), para que los scripts vengan ejecutables al
clonar/actualizar (Windows no guarda el bit de ejecución; sin esto, en el servidor sale
`-bash: ./deploy.sh: Permission denied`):

```bash
git update-index --chmod=+x deploy.sh deploy/backup.sh deploy/make-selfsigned-cert.sh
git commit -m "Scripts de despliegue ejecutables"
git push
```

> Mientras no hagas esto, en el servidor ejecuta con **`bash deploy.sh`** (no requiere el bit
> de ejecución) o dale permiso con `chmod +x deploy.sh` antes de `./deploy.sh`.

**Opcional** — sudo sin contraseña para el paso de permisos (despliegues desatendidos):

```bash
sudo visudo -f /etc/sudoers.d/inventario-deploy
# agrega la línea:
deploy ALL=(root) NOPASSWD: /usr/bin/chown, /usr/bin/find, /usr/bin/chmod
```

---

## 1. En tu PC (desarrollo)

Programa, prueba y cuando algo esté listo, súbelo:

```bash
cd C:\xampp\htdocs\laravel\inventario-it

# (opcional) correr pruebas antes de subir
php artisan test

# subir cambios al repositorio remoto
git add .
git commit -m "Descripción del cambio"
git push origin main
```

> Nunca subas el archivo `.env` ni la carpeta `vendor/` ni `node_modules/` (ya están en `.gitignore`).

---

## 2. En el servidor de producción

Entra como el usuario `deploy` y ejecuta el script (que hace el `git pull` y todo lo demás).
**No lo corras con `sudo`** — se ejecuta como `deploy`; solo el paso de permisos usa sudo internamente.

```bash
ssh deploy@IP-DEL-SERVIDOR
cd /var/www/html/inventario-it
./deploy.sh
# si diera 'command not found' (sin bit de ejecución):  bash deploy.sh
```

Eso: baja el código nuevo, instala dependencias, compila assets, corre migraciones,
limpia/cachea y ajusta propietario/permisos. Si algo falla, te avisa y deja la app operable.

### Variables opcionales del deploy

```bash
BRANCH=main ./deploy.sh                     # desplegar otra rama
SKIP_GIT=1 ./deploy.sh                       # no tocar git (solo recompilar/migrar)
SKIP_PERMS=1 ./deploy.sh                      # no ajustar propietario/permisos
DEPLOY_OWNER=deploy:www-data ./deploy.sh      # propietario a fijar (default ya es este)
PHP_BIN=/usr/bin/php8.2 ./deploy.sh           # binarios si no están en PATH
```

---

## 3. Reglas de oro

- **No edites archivos en el servidor** (sobre todo `deploy.sh`). Todo cambio se hace en tu PC y se sube por git. Editar en el servidor rompe el `git pull`.
- **No corras `sudo ./deploy.sh`**: se corre como `deploy` (`./deploy.sh`); el script usa `sudo` solo donde lo necesita.
- **Migraciones nuevas**: el `deploy.sh` las corre solo (`migrate --force`). Antes de un despliegue con migraciones, haz respaldo: `./deploy/backup.sh`.
- **Datos**: en producción, el seed carga solo lo base (sin empleados de prueba):
  `php artisan db:seed --class=ProductionSeeder --force`.
- **Contraseñas** (solo primera instalación): `php artisan user:password <correo>`.

---

## 4. El servidor es un ESPEJO del remoto

Regla de oro: **nunca hagas `git commit` ni edites archivos en el servidor.** El `deploy.sh`
sincroniza con `git fetch` + `git reset --hard origin/main`, es decir, **fuerza al servidor a
quedar idéntico al remoto** y descarta cualquier cambio/commit local. Esto evita de raíz los
líos de git (cambios de permisos, ramas divergentes, ediciones locales).

`git reset --hard` **no toca** archivos ignorados: `.env`, `storage/app` (uploads/logo),
`vendor/` y `node_modules/` se conservan.

Si alguna vez el `git pull` manual se queja de "divergent branches" (porque quedó un commit
local viejo en el servidor), límpialo una vez:

```bash
cd /var/www/html/inventario-it
git fetch origin
git reset --hard origin/main      # servidor idéntico al remoto
./deploy.sh
```

Recordatorio de causas ya resueltas por el script:
- `command not found` / `Permission denied` en `./deploy.sh` → faltaba el bit de ejecución (Windows no lo guarda; el `reset --hard` lo deja sin permiso). Solución inmediata: `bash deploy.sh`. Permanente: marcar los scripts ejecutables en git (sección 0).
- `Operation not permitted` en chmod → archivos creados por www-data; el script hace `sudo chown` antes.
- `M storage/.../.gitignore` / ramas divergentes → el `reset --hard` + `core.fileMode false` los eliminan.

---

## 5. Rollback rápido (si un despliegue sale mal)

```bash
cd /var/www/html/inventario-it
php artisan down
git checkout <commit_estable_anterior>     # git log --oneline para ver los commits
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --force        # solo si el fallo fue por una migración
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Para más detalle, ver `docs/DESPLIEGUE.md`.
