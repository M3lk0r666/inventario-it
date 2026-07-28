# Flujo de trabajo: desarrollo → producción

Ciclo para pasar cambios de tu PC (XAMPP) al servidor de producción (Ubuntu).

```
   PC de desarrollo (XAMPP)                 Repositorio Git                 Servidor de producción (Ubuntu)
   C:\xampp\htdocs\laravel\inventario-it   (GitHub/GitLab/…)                /var/www/inventario-it
   ── programas y pruebas ──►  git push  ──►  (remoto)  ──►  git pull / ./deploy.sh  ──►  en línea
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

Entra como el usuario `deploy` y ejecuta el script de despliegue, que hace el `git pull` y todo lo demás:

```bash
ssh deploy@IP-DEL-SERVIDOR
cd /var/www/inventario-it
./deploy.sh
```

Eso: baja el código nuevo, instala dependencias, compila assets, corre migraciones y limpia/cachea. Si algo falla, te avisa y deja la app operable.

### Variables opcionales del deploy

```bash
BRANCH=main ./deploy.sh                    # desplegar otra rama
SKIP_GIT=1 ./deploy.sh                      # no tocar git (solo recompilar/migrar)
DEPLOY_CHOWN=deploy:www-data ./deploy.sh    # ajustar propietario (requiere sudo NOPASSWD)
```

---

## 3. Reglas de oro

- **No edites archivos en el servidor** (sobre todo `deploy.sh`). Todo cambio se hace en tu PC y se sube por git. Editar en el servidor rompe el `git pull`.
- **Migraciones nuevas**: el `deploy.sh` las corre solo (`migrate --force`). Antes de un despliegue con migraciones, haz respaldo: `./deploy/backup.sh`.
- **Datos**: en producción, el seed carga solo lo base (sin empleados de prueba):
  `php artisan db:seed --class=ProductionSeeder --force`.
- **Contraseñas** (solo primera instalación): `php artisan user:password <correo>`.

---

## 4. Si el servidor tiene cambios locales que estorban (como pasó)

Si alguna vez editaste algo en el servidor y el `git pull` se queja:

```bash
cd /var/www/inventario-it
git stash            # guarda/aparta los cambios locales
git pull             # ahora sí baja limpio
./deploy.sh
# si quieres descartar de plano esos cambios locales en vez de guardarlos:
#   git checkout .
```

---

## 5. Rollback rápido (si un despliegue sale mal)

```bash
cd /var/www/inventario-it
php artisan down
git checkout <commit_estable_anterior>     # git log --oneline para ver los commits
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --force        # solo si el fallo fue por una migración
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Para más detalle, ver `docs/DESPLIEGUE.md`.
