FROM drupalci/db-base
MAINTAINER drupalci

RUN apt-key adv --keyserver keyserver.ubuntu.com --recv 7F0CEB10

RUN echo "deb http://repo.mongodb.org/apt/ubuntu "$(lsb_release -sc)"/mongodb-org/3.0 multiverse" > /etc/apt/sources.list.d/mongodb-org-3.0.list

RUN apt-get update

RUN mkdir -p /data/db

RUN apt-get install -y adduser mongodb-org-server mongodb-org-shell

CMD ["mongod"]

EXPOSE 27017
