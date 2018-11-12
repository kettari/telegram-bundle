
# Possible values for ext-name:
# bcmath bz2 calendar ctype curl dba dom enchant exif fileinfo filter
# ftp gd gettext gmp hash iconv imap interbase intl json ldap mbstring
# mcrypt mssql mysql mysqli oci8 odbc opcache pcntl pdo pdo_dblib
# pdo_firebird pdo_mysql pdo_oci pdo_odbc pdo_pgsql pdo_sqlite pgsql
# phar posix pspell readline recode reflection session shmop simplexml
# snmp soap sockets spl standard sybase_ct sysvmsg sysvsem sysvshm tidy
# tokenizer wddx xml xmlreader xmlrpc xmlwriter xsl zip

FROM php:7-fpm

RUN apt-get update && \
    apt-get install -y git

    # memcache
RUN apt-get install -y libmemcached-dev zlib1g-dev \
        && pecl install memcached-3.0.4 \
        && docker-php-ext-enable memcached

    # opcache
RUN docker-php-ext-install opcache && docker-php-ext-enable opcache

    # curl
RUN apt-get install -y curl libcurl4-openssl-dev && \
    docker-php-ext-install curl && docker-php-ext-enable curl

    # json
RUN docker-php-ext-install json && docker-php-ext-enable json

    # gd
RUN apt-get install -y libpng-dev libjpeg-dev && \
    docker-php-ext-install gd && docker-php-ext-enable gd

    # xml
RUN apt-get install -y libxml2-dev && \
    docker-php-ext-install xml && docker-php-ext-enable xml

    # mcrypt
RUN apt-get install -y libmcrypt-dev

#RUN docker-php-ext-install mcrypt && docker-php-ext-enable mcrypt

    # mbstring
RUN docker-php-ext-install mbstring && docker-php-ext-enable mbstring

    # pdo
RUN docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql

    # bcmath
RUN docker-php-ext-install bcmath

    # zip
RUN apt-get install -y zip && docker-php-ext-install zip && docker-php-ext-enable zip

RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

#COPY config/php.ini /usr/local/etc/php/

EXPOSE 9000 9001
CMD ["php-fpm"]