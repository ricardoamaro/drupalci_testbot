FROM debian:wheezy
MAINTAINER Ricardo Amaro mail_at_ricardoamaro.com

# Keep upstart from complaining
RUN dpkg-divert --local --rename --add /sbin/initctl
RUN ln -sf /bin/true /sbin/initctl

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN DEBIAN_FRONTEND=noninteractive apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install build-essential software-properties-common python-software-properties sqlite3 mysql-client postgresql-client apache2 curl git libapache2-mod-php5 openssh-server php5-cli php5-curl php5-gd php5-mysql php5-sqlite php5-pgsql php-apc php-pear php-codesniffer pwgen unzip sudo python-setuptools nano apt-utils tidy vim drush ruby ruby-dev   make re2c bison libxml2-dev libssl-dev libbz2-dev libcurl3-dev libdb5.1-dev libjpeg-dev libpng-dev libXpm-dev libfreetype6-dev libt1-dev libgmp3-dev libc-client-dev libldap2-dev libmcrypt-dev libmhash-dev freetds-dev libz-dev libmysqlclient15-dev ncurses-dev libpcre3-dev unixODBC-dev libsqlite-dev libaspell-dev libreadline6-dev librecode-dev libsnmp-dev libtidy-dev libxslt-dev libt1-dev ccache sudo 

RUN DEBIAN_FRONTEND=noninteractive apt-get autoclean
RUN DEBIAN_FRONTEND=noninteractive apt-get autoremove

# Serverspec
RUN gem install bundler -v 1.6.3 2>/dev/null 
RUN gem install serverspec rake 2>/dev/null

# PHPENV
RUN git clone https://github.com/CHH/phpenv.git /tmp/phpenv
RUN /bin/bash /tmp/phpenv/bin/phpenv-install.sh
RUN scp /tmp/phpenv/extensions/* $HOME/.phpenv/libexec/

RUN echo 'eval "$(phpenv init -)"' >> /$HOME/.bashrc
ENV PATH /home/travis/.phpenv/shims:/home/travis/.phpenv/bin:$PATH

RUN git clone git://github.com/nickschuch/php-build.git $HOME/.phpenv/plugins/php-build
RUN sudo $HOME/.phpenv/plugins/php-build/install.sh

# whitelist = phar
RUN mkdir -p /etc/php5/conf.d/
RUN echo "suhosin.executor.include.whitelist = phar" >> /etc/php5/conf.d/suhosin.ini

# Composer.
RUN curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin -d detect_unicode=0
RUN sudo ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

# Drush and dependencies
RUN /usr/local/bin/composer global require drush/drush:dev-master
RUN /.composer/vendor/drush/drush/drush --version

# Enable a2enmod rewrite
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

# Make start.sh executable
RUN chmod 755 /start.sh
