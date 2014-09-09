FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN apt-get -y update
RUN apt-get -y install python-software-properties && \
    apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db && \
    add-apt-repository 'deb http://ftp.osuosl.org/pub/mariadb/repo/5.5/debian wheezy main' && \
    apt-get -y update && \
    apt-get -y install mariadb-server netcat

RUN apt-get autoclean
RUN apt-get autoremove

RUN sed -i -e"s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
RUN rm -rf /var/lib/mysql/*

USER root
EXPOSE 3306

ADD ./conf/startup.sh /opt/startup.sh

CMD ["bin/bash", "/opt/startup.sh"]
