FROM debian:wheezy
MAINTAINER jthorson@sasktel.net

ENV DEBIAN_FRONTEND noninteractive
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2

RUN apt-get update -y

RUN apt-get install -y wget python-software-properties && apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db && add-apt-repository 'deb http://ftp.osuosl.org/pub/mariadb/repo/5.5/debian wheezy main'

#RUN wget -qO - http://drupalcode.org/project/drupaltestbot-puppet.git/blob_plain/refs/heads/master:/modules/base/files/etc/apt/sources.list.d/php5.4/testbot.public.key | apt-key add -

RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install -y wget git vim locales postfix curl openssh-server apache2 mariadb-server supervisor postgresql sqlite php5 php5-curl libapache2-mod-php5 php5-gd php5-cli php5-xsl php5-imap php5-mcrypt php5-sqlite php5-intl php5-xmlrpc php-pear php5-mysql php5-pgsql

RUN mkdir -p /var/run/sshd
RUN mkdir -p /var/log/supervisor
RUN mkdir -p /var/run/apache2

RUN a2enmod rewrite
#RUN a2enmod ssl

RUN cd /opt && git clone https://github.com/drush-ops/drush.git drush
RUN cd /opt/drush && git fetch --all && git checkout 25f7940e00
RUN ln -s /opt/drush/drush /usr/bin/drush

ADD supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD default /etc/apache2/sites-available/default
ADD php.ini /etc/php5/apache2/php.ini
ADD php-cli.ini /etc/php5/cli/php.ini

#ADD settings.php /var/www/sites/default/settings.php

EXPOSE 22 80

CMD ["/usr/bin/supervisord"]

