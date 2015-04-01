FROM drupalci/db-base
MAINTAINER drupalci

# Packages.
RUN apt-get -qq -y install software-properties-common && \
    apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db && \
    add-apt-repository 'deb http://ftp.osuosl.org/pub/mariadb/repo/5.5/ubuntu trusty main' && \
    apt-get -y update && \
    apt-get -y install mariadb-server netcat

RUN apt-get clean && apt-get autoclean && apt-get -y autoremove

RUN sed -i -e"s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
RUN rm -rf /var/lib/mysql/*

USER root
EXPOSE 3306

COPY ./conf/startup.sh /opt/startup.sh

CMD ["/bin/bash", "/opt/startup.sh"]
