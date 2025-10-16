FROM mysql:8.0

# Configuration personnalisée MySQL
COPY docker/mysql/conf.d/my.cnf /etc/mysql/conf.d/my.cnf
RUN chmod 0444 /etc/mysql/conf.d/my.cnf

# Point de montage pour les données
VOLUME /var/lib/mysql

# Port exposé
EXPOSE 3306
