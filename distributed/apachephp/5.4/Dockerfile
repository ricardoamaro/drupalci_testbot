FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive
ENV APACHE_RUN_USER www-data 
ENV APACHE_RUN_GROUP www-data 
ENV APACHE_LOG_DIR /var/log/apache2

RUN apt-get update -y && apt-get upgrade -y && apt-get install -y wget git vim locales curl mysql-client openssh-server apache2 supervisor php5 php5-curl libapache2-mod-php5 php5-gd php5-cli php5-xsl php5-imap php5-mcrypt php5-intl php5-xmlrpc php-pear php5-mysql 

RUN mkdir -p /var/run/sshd && mkdir -p /var/log/supervisor && mkdir -p /var/run/apache2 && a2enmod rewrite

#RUN cd /opt && git clone https://github.com/drush-ops/drush.git drush && cd /opt/drush && git fetch --all && git checkout 25f7940e00 && ln -s /opt/drush/drush /user/bin/drush

ADD ./files/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD ./files/php.ini /etc/php5/apache2/php.ini
ADD ./files/php-cli.ini /etc/php5/cli/php.ini
ADD ./files/drupalsite /etc/apache2/sites-available/drupalsite

RUN a2ensite drupalsite

RUN echo "root:drupaltestbot" |chpasswd

EXPOSE 22 80

CMD ["usr/bin/", "/bin/bash"]
