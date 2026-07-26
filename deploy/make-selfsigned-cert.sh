#!/usr/bin/env bash
#
# make-selfsigned-cert.sh — Genera un certificado autofirmado local para
# servir el portal por HTTPS cuando NO se usa un dominio público / Let's Encrypt
# (p.ej. red interna con IP o nombre local como inventario.local).
#
# Uso (como root/sudo):
#   sudo ./deploy/make-selfsigned-cert.sh inventario.local
#   sudo CN=192.168.1.10 ./deploy/make-selfsigned-cert.sh
#
set -euo pipefail

CN="${1:-${CN:-inventario.local}}"
SSL_DIR="/etc/ssl/inventario-it"
DAYS="${DAYS:-3650}"

echo "==> Generando certificado autofirmado para CN=${CN} (válido ${DAYS} días)"
mkdir -p "$SSL_DIR"

# -subj evita el cuestionario interactivo; -addext agrega SAN (recomendado
# para navegadores modernos: el CN por sí solo ya no basta).
openssl req -x509 -nodes -days "$DAYS" \
    -newkey rsa:2048 \
    -keyout "$SSL_DIR/inventario-it.key" \
    -out "$SSL_DIR/inventario-it.crt" \
    -subj "/C=MX/O=NETJER Networks/CN=${CN}" \
    -addext "subjectAltName=DNS:${CN},DNS:localhost,IP:127.0.0.1"

chmod 600 "$SSL_DIR/inventario-it.key"
chmod 644 "$SSL_DIR/inventario-it.crt"

echo "==> Listo:"
echo "    Certificado: $SSL_DIR/inventario-it.crt"
echo "    Llave:       $SSL_DIR/inventario-it.key"
echo
echo "Siguiente paso: apúntalos en el VirtualHost SSL (ver deploy/apache-inventario-ssl.conf)"
echo "y activa SSL:  sudo a2enmod ssl && sudo systemctl reload apache2"
echo
echo "NOTA: al ser autofirmado, el navegador mostrará una advertencia la primera vez."
echo "Para quitarla, importa inventario-it.crt como 'entidad de confianza' en los equipos"
echo "cliente (o distribúyelo por GPO en el dominio)."
