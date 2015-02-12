FROM debian:wheezy
MAINTAINER Ricardo Amaro mail_at_ricardoamaro.com

ENV DEBIAN_FRONTEND noninteractive

# Keep upstart from complaining
RUN dpkg-divert --local --rename --add /sbin/initctl
RUN ln -sf /bin/true /sbin/initctl

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN echo deb http://http.debian.net/debian wheezy-backports main >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get -y install apache2 apache2-prefork-dev apt-utils bison \
    build-essential ccache curl drush freetds-dev git libapache2-mod-php5 \
    libaspell-dev libbz2-dev libc-client-dev libcurl4-gnutls-dev libdb5.1-dev \
    libfreetype6-dev libgmp3-dev libjpeg62 libjpeg62-dev \
    libldap2-dev libmcrypt-dev libmhash-dev libmysqlclient15-dev \
    libpcre3-dev libpng-dev libreadline6-dev librecode-dev \
    libsnmp-dev libsqlite-dev libssl-dev libt1-dev libtidy-dev \
    libxml2-dev libXpm-dev libxslt1-dev libxslt-dev libz-dev \
    make mysql-client mc nano ncurses-dev openssh-server php5-cli php5-curl \
    php5-gd php5-mysql php5-pgsql php5-sqlite php-apc php-codesniffer \
    php-pear postgresql-client pwgen python-setuptools php5-dev \
    php5-mongo \
    re2c ruby ruby-dev sqlite3 sudo tidy time unixODBC-dev unzip vim

RUN apt-get clean
RUN apt-get autoclean
RUN apt-get -y autoremove

# Ruby. (TODO if 2.0 needed)
# curl -L https://get.rvm.io | bash -s stable --autolibs=enabled

# Serverspec.
RUN gem install bundler -v 1.6.3 2>/dev/null
RUN gem install serverspec rake 2>/dev/null

# PHPENV.
ENV PHPENV_ROOT /.phpenv/
RUN git clone https://github.com/CHH/phpenv.git /tmp/phpenv
RUN /bin/bash /tmp/phpenv/bin/phpenv-install.sh
RUN scp /tmp/phpenv/extensions/* /.phpenv/libexec/

RUN echo 'export PATH="/.phpenv/bin:$PATH"' >> $HOME/.bashrc
RUN echo 'eval "$(phpenv init -)"' >> $HOME/.bashrc
ENV PATH /.phpenv/shims:/.phpenv/bin:$PATH

RUN git clone https://github.com/CHH/php-build.git /.phpenv/plugins/php-build
RUN sudo /.phpenv/plugins/php-build/install.sh

# whitelist = phar
RUN mkdir -p /etc/php5/conf.d/
RUN echo "suhosin.executor.include.whitelist = phar" >> /etc/php5/conf.d/suhosin.ini

# Composer.
RUN curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin -d detect_unicode=0
RUN sudo ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

# Drush and dependencies.
RUN HOME=/ /usr/local/bin/composer global require drush/drush:dev-master
RUN /.composer/vendor/drush/drush/drush --version

# Enable a2enmod rewrite.
RUN a2enmod rewrite php5

# Supervisord.
RUN easy_install supervisor
ADD ./conf/supervisor/supervisord.conf /etc/supervisord.conf
RUN mkdir /var/log/supervisor/

# Sudo.
RUN echo %sudo  ALL=NOPASSWD: ALL >> /etc/sudoers
RUN mkdir /var/run/sshd

# Scripts.
ADD ./conf/scripts/start.sh /start.sh
ADD ./conf/mongodb.settings.php /mongodb.settings.php
ADD ./conf/scripts/foreground.sh /etc/apache2/foreground.sh

# Apache.
RUN rm -rf /var/www/*
RUN chmod 755 /etc/apache2/foreground.sh

# Apache / PHP.
ADD ./conf/apache2/vhost.conf /etc/apache2/sites-available/default
ADD ./conf/php5/apache2.ini /etc/php5/apache2/php.ini
ADD ./conf/php5/cli.ini /etc/php5/cli/php.ini
ADD ./conf/php5/apc.ini /etc/php5/conf.d/apc.ini

EXPOSE 80

# Make start.sh executable.
RUN chmod 755 /start.sh
