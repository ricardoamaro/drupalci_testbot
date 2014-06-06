FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -y update
RUN apt-get -y install mysql-server
RUN sed -i -e"s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
RUN rm -rf /var/lib/mysql/*

ADD ./conf/startup.sh /opt/startup.sh

USER root
EXPOSE 3306
CMD ["bin/bash", "/opt/startup.sh"]
