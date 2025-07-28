# Server Installation

## Basic Setup
0. Install PHP 7.x (with mbstring module), MySQL/MariaDB and Apache2 on a Linux server (Debian recommended).
   ```
   apt install php php-mbstring mariadb-server apache2 libapache2-mod-php
   ```
1. Download the [latest release](https://github.com/schorschii/fluentdb/releases) and copy/extract all files into `/srv/www/fluentdb`.
2. Configure your Apache webserver
   - configure your web sever to use the `/srv/www/fluentdb/frontend` directory as webroot
   - make sure that `AllowOverride All` is set (for the frontend directory `/srv/www/fluentdb/frontend`) so that the `.htaccess` files work
   - make sure that the `rewrite` module is installed and enabled
     ```
     a2enmod rewrite
     service apache2 restart
     ```
3. Import the database schema (including all schema upgrades, `/sql/*.sql`) into an empty database.
   ```
   root@server:/# mysql
   mysql> CREATE DATABASE fluentdb DEFAULT CHARACTER SET utf8mb4;
   mysql> CREATE USER 'fluentdb'@'localhost' IDENTIFIED BY 'choose_your_own_password';
   mysql> GRANT ALL PRIVILEGES ON fluentdb.* TO 'fluentdb'@'localhost';
   mysql> FLUSH PRIVILEGES;
   mysql> EXIT;
   root@server:/# cat sql/*.sql | mysql fluentdb
   ```
4. Create the configuration file `conf.php` (create this file by copying the template `conf.example.php`).
   - Enter your MySQL credentials in `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`. Use a separate database user for the database connection which only has permission to read and write in the specific database. Do not use the root account.
5. **Important:** set up HTTPS with a valid certificate and configure your web server to redirect any HTTP request to HTTPS.
   - Redirect all HTTP requests to HTTPS using appropriate rewrite rules.  
     <details>
     <summary>/etc/apache2/sites-enabled/000-default.conf</summary>

     ```
     <VirtualHost *:80>
        .....
        DocumentRoot /srv/www/fluentdb/frontend
        ## Redirect to HTTPS
        RewriteEngine On
        RewriteCond %{HTTPS} !=on
        RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]
        .....
     </VirtualHost>

     <VirtualHost *:443>
      .....
      DocumentRoot /srv/www/fluentdb/frontend
      SSLEngine on
      SSLCertificateFile /etc/apache2/ssl/mycertwithchain.crt
      SSLCertificateKeyFile /etc/apache2/ssl/myprivkey.key
      .....
      <Directory /srv/www/fluentdb/frontend>
        AllowOverride All
      </Directory>
      .....
     </VirtualHost>
     ```
     </details>
   - The next section describes in detail how to obtain a LetsEncrypt certificate. It is also possible to use a self-signed certificate if necessary. Then, you have to import your own CA certificate into the trust store of every agent's operating system.
   - After you have sucessfully set up HTTPS, please enable the option `php_value session.cookie_secure 1` in the `frontend/.htaccess` file to ensure cookies are only transferred via HTTPS.
8. Use a web browser to open the web frontend. The setup page should appear which allows you to create an admin user account.

### Obtaining A Let’s Encrypt Certificate
1. Enable the Apache SSL module: `a2enmod ssl`
2. Install LetsEncrypts certbot: `apt-get install python-certbot-apache`
3. Obtain a certificate: `certbot --apache certonly -d example.com`.  
   This requires that your server is (temporarily) available from the internet, so that LetsEncrypt can contact it.  
   Certificate files (private key + certificate, chain) will be saved in '/etc/letsencrypt/live/example.com'.
4. Certificate can be renewed using `certbot --apache renew`.

### fail2ban
You can set up fail2ban to prevent brute force attacks. Example configuration can be found in the `examples/fail2ban` directory.

### LDAP Sync & Authentication
If you want to use LDAP to authenticate admin users on the web frontend, please follow this steps.

1. Enter your LDAP details in `conf.php`:
   - `LDAP_SERVER`: 'ldap://192.168.56.101' (single) or 'ldaps://192.168.56.101' (secure) or 'ldaps://192.168.56.101 ldaps://192.168.56.102' (multiple) or »null« (disabled).
   - `LDAP_USERNAME`: The username of the LDAP reader user.
   - `LDAP_PASSWORD`: The password of the LDAP reader user.
   - `LDAP_QUERY_ROOT`: The query root, e.g. 'OU=Benutzer,DC=sieber,DC=systems'.
   - `LDAP_FILTER`: The filter for user objects, e.g. '(objectClass=user)' for ActiveDirectory, 'inetorgperson' for OpenLDAP.
   - `LDAP_ATTR_UID`, `LDAP_ATTR_USERNAME`, `LDAP_ATTR_TITLE`: LDAP attributes to query. Set for Active Directory by default; you can adjust it if you are using an other LDAP server like OpenLDAP.
2. Set up a cron job executing `php console.php ldapsync` every 30 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /srv/www/fluentdb && php console.php ldapsync
   ```
3. Start the first sync manually by executing `cd /srv/www/fluentdb && php console.php ldapsync`.  
   Now you can log in with the synced accounts on the web frontend.
