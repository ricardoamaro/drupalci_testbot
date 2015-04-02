FROM       drupalci/base
MAINTAINER drupalci

##
# Base
##

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

# Saves us from stale repository issues.
RUN apt-get clean && apt-get update

# Build packages.
# Make the PHP compiles go faster.
# re2c and bison are needed for compiling php7
# apache2-dev brings apxs2 into the game which is neede to compile php
RUN apt-get install -y \
    bison \
    ccache \
    curl \
    freetds-dev \
    git \
    htop \
    libaspell-dev \
    libbz2-dev \
    libc-client-dev \
    libcurl3-dev \
    libcurl4-openssl-dev \
    libdb5.1-dev \
    libfreetype6-dev \
    libfreetype6-dev \
    libgmp3-dev \
    libicu-dev \
    libjpeg-dev \
    libjpeg-dev \
    libldap2-dev \
    libldap2-dev \
    libmcrypt-dev \
    libmhash-dev \
    libmysqlclient-dev \
    libmysqlclient15-dev \
    libpcre3-dev \
    libpng-dev \
    libpng-dev \
    libpq-dev \
    libreadline6-dev \
    librecode-dev \
    libsnmp-dev \
    libsqlite-dev \
    libt1-dev \
    libt1-dev \
    libtidy-dev \
    libxml2-dev \
    libxml2-dev libssl-dev \
    libxpm-dev \
    libXpm-dev \
    libxslt-dev \
    libxslt-dev \
    libz-dev \
    make \
    mc \
    mysql-client \
    ncurses-dev \
    php5-dev \
    re2c \
    sudo \
    unixODBC-dev \
    unzip \
    supervisor \
    sqlite3

RUN apt-get clean && apt-get autoremove -y

##
# PHPENV.
##

RUN git clone --depth 1 https://github.com/CHH/phpenv.git /tmp/phpenv
RUN /tmp/phpenv/bin/phpenv-install.sh
RUN scp /tmp/phpenv/extensions/* /root/.phpenv/libexec/

RUN echo 'eval "$(phpenv init -)"' >> /root/.bashrc
ENV PATH /root/.phpenv/shims:/root/.phpenv/bin:$PATH

RUN git clone --depth 1 https://github.com/CHH/php-build.git /root/.phpenv/plugins/php-build

# TODO: Make sure we can read phpenv in a better way
RUN chmod 755 /root/

# Small hack for running the php compilation with more than one cpu core
#RUN mv /usr/bin/make /usr/bin/make-system
#RUN echo "/usr/bin/make-system -j8 -l8" > /usr/bin/make
#RUN chmod +x /usr/bin/make

##
# Composer.
##

RUN bash -c "wget http://getcomposer.org/composer.phar && chmod 775 composer.phar && sudo mv composer.phar /usr/local/bin/composer"

# Drush and dependencies.
RUN HOME=/ /usr/local/bin/composer global require drush/drush:dev-master
RUN /.composer/vendor/drush/drush/drush --version

# supervisor
COPY ./conf/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Scripts.
COPY ./conf/scripts/start.sh /start.sh
COPY ./conf/mongodb.settings.php /mongodb.settings.php
COPY ./conf/scripts/foreground.sh /etc/apache2/foreground.sh

# Make start.sh executable.
RUN chmod 755 /start.sh

