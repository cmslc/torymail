#!/bin/bash
###############################################
# Torymail VPS Auto Setup Script
# OS: Debian 12 (Bookworm)
# Run: bash setup-vps.sh
###############################################

set -e

# ============ CONFIG ============
DOMAIN="torycms.com"
MAIL_HOSTNAME="mail.torycms.com"
DB_NAME="torymail"
DB_USER="torymail"
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=')
ENCRYPTION_KEY=$(openssl rand -hex 32)
WEB_ROOT="/var/www/torymail"
MAIL_STORAGE="/var/mail/vhosts"

echo "========================================="
echo "  Torymail VPS Setup - Debian 12"
echo "========================================="
echo ""
echo "Domain: $DOMAIN"
echo "Mail Hostname: $MAIL_HOSTNAME"
echo "DB Name: $DB_NAME"
echo "DB User: $DB_USER"
echo ""

# ============ 1. UPDATE SYSTEM ============
echo "[1/10] Updating system..."
apt update && apt upgrade -y

# ============ 2. INSTALL APACHE ============
echo "[2/10] Installing Apache..."
apt install -y apache2
a2enmod rewrite ssl headers
systemctl enable apache2

# ============ 3. INSTALL PHP 8.2 ============
echo "[3/10] Installing PHP 8.2..."
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
    php8.2-imap php8.2-opcache libapache2-mod-php8.2
a2enmod php8.2

# ============ 4. INSTALL MARIADB ============
echo "[4/10] Installing MariaDB..."
apt install -y mariadb-server mariadb-client
systemctl enable mariadb
systemctl start mariadb

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "  DB Password: $DB_PASS"

# ============ 5. INSTALL POSTFIX ============
echo "[5/10] Installing Postfix..."
debconf-set-selections <<< "postfix postfix/mailname string $MAIL_HOSTNAME"
debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
apt install -y postfix

# Configure Postfix
cat > /etc/postfix/main.cf << POSTFIX_EOF
# Basic settings
smtpd_banner = \$myhostname ESMTP
biff = no
append_dot_mydomain = no

# TLS parameters (will be updated after certbot)
smtpd_tls_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
smtpd_tls_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
smtpd_use_tls=yes
smtpd_tls_session_cache_database = btree:\${data_directory}/smtpd_scache
smtp_tls_session_cache_database = btree:\${data_directory}/smtp_scache
smtpd_tls_security_level=may
smtp_tls_security_level=may
smtpd_tls_protocols = !SSLv2, !SSLv3, !TLSv1, !TLSv1.1
smtp_tls_protocols = !SSLv2, !SSLv3, !TLSv1, !TLSv1.1

# Network settings
myhostname = $MAIL_HOSTNAME
mydomain = $DOMAIN
myorigin = \$mydomain
mydestination = localhost
mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128
inet_interfaces = all
inet_protocols = all

# Virtual mailbox settings
virtual_mailbox_domains = mysql:/etc/postfix/mysql-virtual-domains.cf
virtual_mailbox_maps = mysql:/etc/postfix/mysql-virtual-mailboxes.cf
virtual_alias_maps = mysql:/etc/postfix/mysql-virtual-aliases.cf
virtual_mailbox_base = $MAIL_STORAGE
virtual_minimum_uid = 5000
virtual_uid_maps = static:5000
virtual_gid_maps = static:5000
virtual_transport = lmtp:unix:private/dovecot-lmtp

# Restrictions
smtpd_helo_required = yes
smtpd_recipient_restrictions =
    permit_mynetworks,
    permit_sasl_authenticated,
    reject_unauth_destination,
    reject_invalid_hostname,
    reject_non_fqdn_hostname,
    reject_non_fqdn_sender,
    reject_non_fqdn_recipient,
    reject_unknown_sender_domain,
    reject_unknown_recipient_domain,
    reject_rbl_client zen.spamhaus.org

# SASL authentication
smtpd_sasl_type = dovecot
smtpd_sasl_path = private/auth
smtpd_sasl_auth_enable = yes
smtpd_sasl_security_options = noanonymous
smtpd_sasl_local_domain = \$myhostname

# Message size limit (25MB)
message_size_limit = 26214400
mailbox_size_limit = 0

# Queue settings
maximal_queue_lifetime = 3d
bounce_queue_lifetime = 1d
POSTFIX_EOF

# Postfix MySQL lookups
cat > /etc/postfix/mysql-virtual-domains.cf << EOF
user = $DB_USER
password = $DB_PASS
hosts = 127.0.0.1
dbname = $DB_NAME
query = SELECT domain_name FROM domains WHERE domain_name='%s' AND status='active' AND is_verified=1
EOF

cat > /etc/postfix/mysql-virtual-mailboxes.cf << EOF
user = $DB_USER
password = $DB_PASS
hosts = 127.0.0.1
dbname = $DB_NAME
query = SELECT CONCAT(SUBSTRING_INDEX(email,'@',-1),'/',SUBSTRING_INDEX(email,'@',1),'/') FROM mailboxes WHERE email='%s' AND status='active'
EOF

cat > /etc/postfix/mysql-virtual-aliases.cf << EOF
user = $DB_USER
password = $DB_PASS
hosts = 127.0.0.1
dbname = $DB_NAME
query = SELECT forward_to FROM mailboxes WHERE email='%s' AND forward_enabled=1 AND status='active'
EOF

chmod 640 /etc/postfix/mysql-virtual-*.cf
chown root:postfix /etc/postfix/mysql-virtual-*.cf

# Enable submission port (587)
cat >> /etc/postfix/master.cf << 'MASTER_EOF'

submission inet n       -       y       -       -       smtpd
  -o syslog_name=postfix/submission
  -o smtpd_tls_security_level=encrypt
  -o smtpd_sasl_auth_enable=yes
  -o smtpd_tls_auth_only=yes
  -o smtpd_reject_unlisted_recipient=no
  -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject
  -o milter_macro_daemon_name=ORIGINATING
MASTER_EOF

# ============ 6. INSTALL DOVECOT ============
echo "[6/10] Installing Dovecot..."
apt install -y dovecot-core dovecot-imapd dovecot-lmtpd dovecot-mysql

# Create vmail user
groupadd -g 5000 vmail 2>/dev/null || true
useradd -g vmail -u 5000 -d $MAIL_STORAGE -s /usr/sbin/nologin vmail 2>/dev/null || true
mkdir -p $MAIL_STORAGE
chown -R vmail:vmail $MAIL_STORAGE
chmod -R 770 $MAIL_STORAGE

# Dovecot main config
cat > /etc/dovecot/dovecot.conf << 'DOVECOT_EOF'
protocols = imap lmtp
listen = *, ::
!include conf.d/*.conf
!include_try local.conf
DOVECOT_EOF

# Dovecot mail config
cat > /etc/dovecot/conf.d/10-mail.conf << EOF
mail_location = maildir:$MAIL_STORAGE/%d/%n
mail_privileged_group = vmail
namespace inbox {
  inbox = yes
}
EOF

# Dovecot auth config
cat > /etc/dovecot/conf.d/10-auth.conf << 'EOF'
disable_plaintext_auth = yes
auth_mechanisms = plain login
!include auth-sql.conf.ext
EOF

# Dovecot SQL auth
cat > /etc/dovecot/dovecot-sql.conf.ext << EOF
driver = mysql
connect = host=127.0.0.1 dbname=$DB_NAME user=$DB_USER password=$DB_PASS
default_pass_scheme = BLF-CRYPT
password_query = SELECT email as user, password FROM mailboxes WHERE email='%u' AND status='active'
user_query = SELECT 'vmail' AS uid, 'vmail' AS gid, '$MAIL_STORAGE/%d/%n' AS home
EOF

cat > /etc/dovecot/conf.d/auth-sql.conf.ext << 'EOF'
passdb {
  driver = sql
  args = /etc/dovecot/dovecot-sql.conf.ext
}
userdb {
  driver = static
  args = uid=vmail gid=vmail home=/var/mail/vhosts/%d/%n
}
EOF

# Dovecot master config (LMTP + Auth socket for Postfix)
cat > /etc/dovecot/conf.d/10-master.conf << 'EOF'
service imap-login {
  inet_listener imap {
    port = 0
  }
  inet_listener imaps {
    port = 993
    ssl = yes
  }
}

service lmtp {
  unix_listener /var/spool/postfix/private/dovecot-lmtp {
    mode = 0600
    user = postfix
    group = postfix
  }
}

service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0660
    user = postfix
    group = postfix
  }
  unix_listener auth-userdb {
    mode = 0600
    user = vmail
  }
  user = dovecot
}

service auth-worker {
  user = vmail
}
EOF

# Dovecot SSL config
cat > /etc/dovecot/conf.d/10-ssl.conf << 'EOF'
ssl = required
ssl_cert = </etc/ssl/certs/ssl-cert-snakeoil.pem
ssl_key = </etc/ssl/private/ssl-cert-snakeoil.key
ssl_min_protocol = TLSv1.2
EOF

chmod 600 /etc/dovecot/dovecot-sql.conf.ext

# ============ 7. INSTALL CERTBOT ============
echo "[7/10] Installing Certbot..."
apt install -y certbot python3-certbot-apache

# ============ 8. DEPLOY TORYMAIL ============
echo "[8/10] Deploying Torymail..."
apt install -y git
mkdir -p $WEB_ROOT
git clone https://github.com/cmslc/torymail.git $WEB_ROOT
chown -R www-data:www-data $WEB_ROOT
chmod -R 755 $WEB_ROOT
chmod -R 775 $WEB_ROOT/storage

# Import database schema
mysql $DB_NAME < $WEB_ROOT/migrations/1.0.0.sql

# Create .env file
cat > $WEB_ROOT/.env << EOF
DB_HOST=localhost
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS
DB_DATABASE=$DB_NAME

MAIL_STORAGE_PATH=$MAIL_STORAGE
MAIL_SERVER_HOSTNAME=$MAIL_HOSTNAME

IMAP_PORT=993
SMTP_PORT=587

ENCRYPTION_KEY=$ENCRYPTION_KEY

APP_URL=https://$MAIL_HOSTNAME
EOF

chown www-data:www-data $WEB_ROOT/.env
chmod 640 $WEB_ROOT/.env

# ============ 9. CONFIGURE APACHE VHOST ============
echo "[9/10] Configuring Apache..."
cat > /etc/apache2/sites-available/torymail.conf << EOF
<VirtualHost *:80>
    ServerName $MAIL_HOSTNAME
    ServerAlias $DOMAIN
    DocumentRoot $WEB_ROOT

    <Directory $WEB_ROOT>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/torymail-error.log
    CustomLog \${APACHE_LOG_DIR}/torymail-access.log combined
</VirtualHost>
EOF

a2dissite 000-default.conf 2>/dev/null || true
a2ensite torymail.conf
systemctl restart apache2

# ============ 10. SETUP CRON ============
echo "[10/10] Setting up cron job..."
(crontab -l 2>/dev/null; echo "* * * * * php $WEB_ROOT/cron/cron.php >> /var/log/torymail-cron.log 2>&1") | crontab -

# ============ RESTART SERVICES ============
echo ""
echo "Restarting all services..."
systemctl restart postfix
systemctl restart dovecot
systemctl restart apache2
systemctl enable postfix dovecot apache2

# ============ FIREWALL ============
echo "Configuring firewall..."
apt install -y ufw
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw allow 25/tcp    # SMTP
ufw allow 587/tcp   # Submission
ufw allow 993/tcp   # IMAPS
ufw --force enable

# ============ DONE ============
echo ""
echo "========================================="
echo "  SETUP COMPLETE!"
echo "========================================="
echo ""
echo "  Web Panel:  http://$MAIL_HOSTNAME"
echo "  Admin:      http://$MAIL_HOSTNAME/auth/login"
echo ""
echo "  Database:"
echo "    DB Name:  $DB_NAME"
echo "    DB User:  $DB_USER"
echo "    DB Pass:  $DB_PASS"
echo ""
echo "  NEXT STEPS:"
echo "  1. Run installer: http://$MAIL_HOSTNAME/install.php"
echo "  2. Get SSL: certbot --apache -d $MAIL_HOSTNAME"
echo "  3. After SSL, update Dovecot + Postfix certs"
echo "  4. Delete install.php after setup"
echo "  5. Add DNS records: A, MX, SPF, DKIM, DMARC"
echo ""
echo "  SAVE THIS INFO! Credentials above won't be shown again."
echo "========================================="

# Save credentials to file
cat > /root/torymail-credentials.txt << EOF
=== Torymail Server Credentials ===
Date: $(date)

Web Panel: https://$MAIL_HOSTNAME
DB Name: $DB_NAME
DB User: $DB_USER
DB Pass: $DB_PASS
Encryption Key: $ENCRYPTION_KEY

DNS Records needed:
  A     mail    198.252.103.31
  MX    @       mail.torycms.com (priority 10)
  TXT   @       v=spf1 mx a ip4:198.252.103.31 ~all
  TXT   _dmarc  v=DMARC1; p=quarantine; rua=mailto:admin@$DOMAIN
EOF

chmod 600 /root/torymail-credentials.txt
echo "Credentials saved to /root/torymail-credentials.txt"
