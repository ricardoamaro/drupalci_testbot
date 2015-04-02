FROM       drupalci/php-base
MAINTAINER drupalci

##
# Base.
##

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

# Saves us from stale repository issues.
RUN apt-get clean && apt-get update

# Install Apache2 and Apache prefork
RUN apt-get install -y apache2 apache2-mpm-prefork apache2-dev
RUN a2dismod mpm_event && a2enmod mpm_prefork

# TODO: Remove the native php version
RUN apt-get -y remove php5-cli

RUN apt-get clean && apt-get autoremove -y

##
# PHPENV.
##

# Remove fpm since apxs2 (apache) support is being compiled.
RUN sed -i '/--enable-fpm/d' /root/.phpenv/plugins/php-build/share/php-build/default_configure_options
RUN echo "--with-apxs2=/usr/bin/apxs2" >> /root/.phpenv/plugins/php-build/share/php-build/default_configure_options
RUN echo "--with-pdo-pgsql" >> /root/.phpenv/plugins/php-build/share/php-build/default_configure_options
RUN sudo /root/.phpenv/plugins/php-build/install.sh
