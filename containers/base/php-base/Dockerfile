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
RUN apt-get install -y curl htop git unzip ccache sudo \
    libxml2-dev \
    libcurl4-openssl-dev \
    libjpeg-dev \
    libpng-dev \
    libxpm-dev \
    libmysqlclient-dev \
    libpq-dev \
    libicu-dev \
    libfreetype6-dev \
    libldap2-dev \
    libxslt-dev \
    php5-dev \
    re2c \
    bison \
    apache2-dev

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

##
# Composer.
##

RUN bash -c "wget http://getcomposer.org/composer.phar && chmod 775 composer.phar && sudo mv composer.phar /usr/local/bin/composer"
