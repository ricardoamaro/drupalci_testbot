FROM debian:wheezy

RUN apt-key adv --keyserver keyserver.ubuntu.com --recv 7F0CEB10

RUN echo 'deb http://downloads-distro.mongodb.org/repo/debian-sysvinit dist 10gen' | tee /etc/apt/sources.list.d/mongodb.list

RUN apt-get update

RUN mkdir -p /data/db

RUN apt-get install -y adduser mongodb-org-server mongodb-org-shell

CMD ["mongod"]

EXPOSE 27017
