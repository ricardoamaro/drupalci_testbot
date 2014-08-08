FROM drupalci/web-base

# Dotdeb
RUN echo "deb http://packages.dotdeb.org wheezy-php55 all" > /etc/apt/sources.list.d/Dotdeb.php55.list
RUN gpg --keyserver keys.gnupg.net --recv-key 89DF5277
RUN gpg -a --export 89DF5277 | apt-key add -

RUN DEBIAN_FRONTEND=noninteractive apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install libapache2-mod-php5 php5-curl php5-gd php5-mysql php5-sqlite php5-pgsql php-pear php-codesniffer php5

CMD ["/bin/bash", "/start.sh"]