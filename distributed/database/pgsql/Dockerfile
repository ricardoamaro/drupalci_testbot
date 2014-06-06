FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

# update the apt cache
RUN apt-get -y update

# Install Postgresql
RUN apt-get -y install postgresql postgresql-client

# Adjust PostgreSQL configuration so that remote connections to the database are possible.
RUN echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/9.1/main/pg_hba.conf
RUN echo "listen_addresses='*'" >> /etc/postgresql/9.1/main/postgresql.conf

ADD ./conf/startup.sh /opt/startup.sh


# Expose the PostgreSQL port
EXPOSE 5432

USER postgres
CMD ["bin/bash", "/opt/startup.sh"]
