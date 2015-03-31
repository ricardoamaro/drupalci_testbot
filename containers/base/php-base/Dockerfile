FROM       drupalci/base
MAINTAINER drupalci

##
# Base.
##

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

# Saves us from stale repository issues.
RUN apt-get clean && apt-get update

# Some base packages.
RUN apt-get install -y make re2c bison libxml2-dev libssl-dev libbz2-dev libcurl3-dev libdb5.1-dev libjpeg-dev libpng-dev libXpm-dev libfreetype6-dev libt1-dev libgmp3-dev libc-client-dev libldap2-dev libmcrypt-dev libmhash-dev freetds-dev libz-dev libmysqlclient15-dev ncurses-dev libpcre3-dev unixODBC-dev libsqlite-dev libaspell-dev libreadline6-dev librecode-dev libsnmp-dev libtidy-dev libxslt-dev libt1-dev

# Tools for phpenv.
RUN apt-get build-dep -y php5-cli

# Make the PHP compiles go faster.
# Ensure we can sudo.
RUN apt-get install -y ccache sudo

RUN apt-get clean && apt-get autoremove -y

##
# PHPENV.
##

RUN git clone https://github.com/CHH/phpenv.git /tmp/phpenv
RUN /tmp/phpenv/bin/phpenv-install.sh
RUN scp /tmp/phpenv/extensions/* /root/.phpenv/libexec/

RUN echo 'eval "$(phpenv init -)"' >> /root/.bashrc
ENV PATH /root/.phpenv/shims:/root/.phpenv/bin:$PATH

RUN git clone --depth 1 git://github.com/CHH/php-build.git /root/.phpenv/plugins/php-build
RUN sudo /root/.phpenv/plugins/php-build/install.sh

##
# Composer.
##

RUN bash -c "wget http://getcomposer.org/composer.phar && chmod 775 composer.phar && sudo mv composer.phar /usr/local/bin/composer"

WORKDIR /data
