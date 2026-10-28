#!/bin/sh
# Démarrage du conteneur : configuration de msmtp (envoi des e-mails de mail()) puis Apache.
# Variables : SMTP_USER (adresse Gmail), SMTP_PASSWORD (mot de passe d'application),
# et pour un autre relais SMTP_HOST, SMTP_PORT, SMTP_TLS (on/off).
set -e

if [ -n "$SMTP_USER" ]; then
    umask 077
    {
        echo "defaults"
        echo "tls ${SMTP_TLS:-on}"
        echo "tls_trust_file /etc/ssl/certs/ca-certificates.crt"
        echo "logfile /tmp/msmtp.log"
        echo "account default"
        echo "host ${SMTP_HOST:-smtp.gmail.com}"
        echo "port ${SMTP_PORT:-587}"
        echo "from $SMTP_USER"
        if [ -n "$SMTP_PASSWORD" ]; then
            # Gmail affiche les mots de passe d'application par groupes de 4 : les espaces ne comptent pas.
            echo "auth on"
            echo "user $SMTP_USER"
            echo "password \"$(printf '%s' "$SMTP_PASSWORD" | tr -d ' ')\""
        else
            echo "auth off"
        fi
    } > /etc/msmtprc
    chown site:site /etc/msmtprc
fi

exec apache2-foreground
