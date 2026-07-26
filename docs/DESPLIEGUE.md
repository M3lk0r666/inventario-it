# Despliegue a producción — Inventario TI

Guía reproducible para instalar el sistema en un servidor **Ubuntu Server + Apache + MySQL** desde cero, con el usuario `deploy`. Sigue los pasos en orden.

> Todos los archivos de apoyo están en la carpeta `deploy/` del proyecto:
> `deploy.sh`, `deploy/make-selfsigned-cert.sh`, `deploy/backup.sh`,
> `deploy/apache-inventario.conf`, `deploy/apache-inventario-ssl.conf`,
> `deploy/env.production.example`, `deploy/crontab.example`.

---

## 1. Requisitos del servidor

- **Ubuntu Server 22.04 LTS** (o superior).
- **PHP 8.2+** con extensiones: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- **MySQL 8** (o MariaDB 10.6+).
- **Apache 2.4** con módulos `rewrite`, `ssl`, `headers`.
- **Composer 2**.
- **Node.js 18+ y npm** (para compilar los assets).
- **Git**.
- Salida a internet al **puerto 587** (correo Office 365) y, si se usa Let's Encrypt, al 80/443.

### 1.1 Instalar paquetes

```bash
sudo apt update
sudo apt install -y apache2 mysql-server git unzip \
  php php-cli php-fpm php-mysql php-mbstring php-xml php-curl php-zip \
  php-gd php-bcmath php-intl libapache2-mod-php
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
# Node LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 1.2 Usuario de despliegue

```bash
sudo adduser deploy
sudo usermod -aG www-data deploy
```

---

## 2. Base de datos

```bash
sudo mysql
```
```sql
CREATE DATABASE inventario_ti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inventario'@'127.0.0.1' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
GRANT ALL PRIVILEGES ON inventario_ti.* TO 'inventario'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3. Código y configuración

```bash
sudo mkdir -p /var/www/inventario-it
sudo chown deploy:www-data /var/www/inventario-it
sudo -u deploy git clone <URL_DEL_REPO> /var/www/inventario-it
cd /var/www/inventario-it

# .env de producción
cp deploy/env.production.example .env
nano .env    # ajustar APP_URL, credenciales de BD, etc.
```

En `.env` verifica: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, datos de `DB_*` y `SESSION_SECURE_COOKIE=true`.

---

## 4. Primer despliegue

El script `deploy.sh` es idempotente (sirve para instalar y para actualizar):

```bash
cd /var/www/inventario-it
chmod +x deploy.sh deploy/*.sh
SKIP_GIT=1 ./deploy.sh     # primera vez: ya clonamos, evitamos git pull
```

Hace: `composer install --no-dev`, `key:generate` (si falta), `npm ci && npm run build`, `migrate --force`, `storage:link`, cachés de config/rutas/vistas, permisos y sale de mantenimiento.

### 4.1 Datos iniciales (solo la primera vez)

```bash
sudo -u deploy php artisan db:seed --force        # catálogos, roles/permisos, usuarios base y configuración
# Definir contraseñas (los usuarios se crean SIN contraseña utilizable):
sudo -u deploy php artisan user:password admin@inventario.test
```

> **Seeders disponibles** (elige según el caso):
> - `php artisan db:seed --class=ProductionSeeder --force` → **solo datos base** (catálogos, roles/permisos, usuarios, configuración). Sin empleados ni datos de prueba. **Usar en producción.**
> - `php artisan db:seed --class=DemoSeeder` → datos de prueba (empleados, activos, asignaciones…); asegura la base automáticamente. **Solo para desarrollo/staging.**
> - `php artisan db:seed --force` (por defecto) → detecta el entorno: en producción corre solo `ProductionSeeder`; fuera de producción corre `DemoSeeder`.

> Entra la primera vez con el Super Admin de arranque (protegido), crea tu propio Super Admin de trabajo y conserva el de contingencia. Ver Configuración → Correo para habilitar el envío (Office 365) y Configuración → Empresa para el logo y textos.

### 4.2 Permisos de carpetas

```bash
sudo chown -R deploy:www-data /var/www/inventario-it
sudo find /var/www/inventario-it -type f -exec chmod 664 {} \;
sudo find /var/www/inventario-it -type d -exec chmod 775 {} \;
sudo chmod -R ug+rwX storage bootstrap/cache
```

---

## 5. Apache (VirtualHost)

```bash
sudo a2enmod rewrite ssl headers
sudo cp deploy/apache-inventario.conf     /etc/apache2/sites-available/inventario-it.conf
sudo cp deploy/apache-inventario-ssl.conf /etc/apache2/sites-available/inventario-it-ssl.conf
# Edita ambos: ServerName (dominio o inventario.local) y rutas de certificado.
sudo nano /etc/apache2/sites-available/inventario-it-ssl.conf
```

---

## 6. HTTPS

### Opción A — Certificado autofirmado local (red interna / sin dominio público)

Recomendado cuando el servidor no tiene dominio público (acceso por IP o nombre local).

```bash
# Genera el certificado (CN = nombre o IP con que accederás)
sudo ./deploy/make-selfsigned-cert.sh inventario.local
#   o por IP:  sudo CN=192.168.1.10 ./deploy/make-selfsigned-cert.sh
```

Esto crea `/etc/ssl/inventario-it/inventario-it.crt` y `.key`, ya apuntados en `apache-inventario-ssl.conf` (Opción A). Al ser autofirmado, el navegador mostrará una advertencia la primera vez; para quitarla, importa el `.crt` como entidad de confianza en los equipos cliente (o distribúyelo por GPO en el dominio).

### Opción B — Let's Encrypt (dominio público)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d tu-dominio.com
```
Luego, en `apache-inventario-ssl.conf`, comenta las líneas de la Opción A y descomenta las de Let's Encrypt.

### Activar el sitio

```bash
sudo a2ensite inventario-it inventario-it-ssl
sudo a2dissite 000-default default-ssl 2>/dev/null || true
sudo apache2ctl configtest      # debe decir "Syntax OK"
sudo systemctl reload apache2
```

Prueba en el navegador: `https://inventario.local` (o tu dominio/IP).

---

## 7. Tareas programadas (cron)

Para que funcionen las **alertas por correo** (renovaciones, garantías, stock) y los **respaldos**:

```bash
sudo -u deploy crontab -e
# pega el contenido de deploy/crontab.example, ajustando la ruta
```

Contenido (ver `deploy/crontab.example`):
```cron
* * * * * cd /var/www/inventario-it && php artisan schedule:run >> /dev/null 2>&1
30 2 * * * BACKUP_DIR=/var/backups/inventario-it RETENTION_DAYS=14 /var/www/inventario-it/deploy/backup.sh >> /var/log/inventario-backup.log 2>&1
```

---

## 8. Respaldos y restauración

- **Respaldo manual:** `sudo -u deploy ./deploy/backup.sh` (BD comprimida + `storage/app/public`, conserva 14 días en `/var/backups/inventario-it`).
- **Restaurar BD:**
  ```bash
  gunzip < /var/backups/inventario-it/db_YYYYMMDD_HHMMSS.sql.gz | \
    mysql -u inventario -p inventario_ti
  ```
- **Restaurar storage:**
  ```bash
  tar xzf /var/backups/inventario-it/storage_YYYYMMDD_HHMMSS.tar.gz -C /var/www/inventario-it
  ```

---

## 9. Actualizaciones posteriores

```bash
cd /var/www/inventario-it
sudo -u deploy ./deploy.sh        # git pull + composer + build + migrate + cachés
```

---

## 10. Checklist de puesta en marcha

- [ ] `APP_ENV=production` y `APP_DEBUG=false` en `.env`.
- [ ] `APP_KEY` generada.
- [ ] BD creada, migrada y sembrada; contraseñas de usuarios definidas (`user:password`).
- [ ] `npm run build` ejecutado (existe `public/build/`).
- [ ] `storage:link` hecho y permisos de `storage/` y `bootstrap/cache` correctos.
- [ ] Apache con `rewrite`, `ssl`, `headers`; `configtest` = OK.
- [ ] HTTPS operativo (autofirmado o Let's Encrypt); HTTP redirige a HTTPS.
- [ ] Cron del scheduler corriendo (`schedule:run`) y respaldo diario programado.
- [ ] Correo configurado y probado (Configuración → Correo → "Probar").
- [ ] Datos de empresa/logo cargados (Configuración → Empresa).
- [ ] Página de bienvenida carga en `/` con botón "Acceder al portal".
- [ ] Login OK; roles muestran solo lo permitido; `/register` responde 404.
- [ ] `php artisan test` en verde (en un entorno de staging).

---

## 11. Rollback

Si un despliegue sale mal:

```bash
cd /var/www/inventario-it
sudo -u deploy php artisan down
git log --oneline -n 5                      # identifica el commit estable anterior
sudo -u deploy git checkout <commit_estable>
sudo -u deploy composer install --no-dev --optimize-autoloader
sudo -u deploy npm ci && npm run build
# Si el fallo fue por una migración:
sudo -u deploy php artisan migrate:rollback --force     # o restaura el respaldo de BD
sudo -u deploy php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo -u deploy php artisan up
```

Para fallos de datos, restaura el último respaldo (sección 8). Mantén siempre el respaldo previo antes de una actualización con migraciones.
