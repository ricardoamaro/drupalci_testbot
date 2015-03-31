FROM       drupalci/base
MAINTAINER drupalci

##
# Base.
##

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

# Saves us from stale repository issues.
RUN apt-get clean && apt-get update

# Build packages.
# Make the PHP compiles go faster.
# Ensure we can sudo.
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
    libxslt-dev

# Tools for phpenv.
RUN apt-get build-dep -y php5-cli

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

