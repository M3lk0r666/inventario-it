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

Y **desde tu PC de desarrollo** (una vez), para que `deploy.sh` venga ejecutable al clonar:

```bash
git update-index --chmod=+x deploy.sh
git commit -m "deploy.sh ejecutable"
git push
```

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

## 4. Si el servidor tiene cambios locales que estorban (como pasó)

Si el `git pull` se queja de cambios locales:

```bash
cd /var/www/html/inventario-it
git config core.fileMode false      # ignora cambios de permisos (quita los 'M' de .gitignore)
git checkout -- deploy.sh           # descarta un deploy.sh editado a mano
git status                          # verifica que quede limpio
git pull                            # ahora sí baja limpio
./deploy.sh
# Alternativa para apartar cambios en vez de descartarlos:  git stash
```

Recordatorio de las causas (ya corregidas en el script):
- `sudo ./deploy.sh: command not found` → faltaba bit de ejecución. Usa `bash deploy.sh` o marca el archivo ejecutable en git (sección 0).
- `Operation not permitted` en chmod → archivos creados por www-data; el script hace `sudo chown` antes.
- `M storage/.../.gitignore` → `chmod -R 775` hacía ejecutables esos archivos; con `git config core.fileMode false` y chmod por tipo (dirs 2775 / archivos 664) ya no pasa.

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
