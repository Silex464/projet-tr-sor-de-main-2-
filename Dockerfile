FROM php:8.2-apache

# Installer l'extension PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Activer mod_rewrite
RUN a2enmod rewrite

# Copier tous les fichiers du projet
COPY . /var/www/html/

# Copier le .htaccess de production
COPY .htaccess.production /var/www/html/.htaccess

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/assets/images/creations \
    && mkdir -p /var/www/html/assets/images/profils \
    && chmod -R 777 /var/www/html/assets/images

# Configurer Apache pour autoriser .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Variables d'environnement par défaut (peuvent être surchargées)
ENV DB_HOST=localhost
ENV DB_NAME=tresordemain
ENV DB_USER=root
ENV DB_PASS=

# Exposer le port 80
EXPOSE 80

# Port dynamique pour Railway/Render
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
