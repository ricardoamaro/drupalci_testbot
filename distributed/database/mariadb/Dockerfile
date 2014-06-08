FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -y update
RUN apt-get -y install python-software-properties && \
    apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db && \
    add-apt-repository 'deb http://ftp.osuosl.org/pub/mariadb/repo/5.5/debian wheezy main' && \
    apt-get -y update && \
    apt-get -y install mariadb-server netcat
RUN sed -i -e"s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
RUN rm -rf /var/lib/mysql/*

ADD ./conf/startup.sh /opt/startup.sh

USER root
EXPOSE 3306
CMD ["bin/bash", "/opt/startup.sh"]
