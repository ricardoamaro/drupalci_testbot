FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -qq update
RUN apt-get -qq -y install locales debconf
ADD conf/locale.gen /etc/locale.gen

# Set a default language, so that PostgreSQL can use UTF-8 encoding (required)
RUN echo 'LANG="en_US.UTF-8"' > /etc/default/locale
RUN echo 'LANGUAGE="en_US:en"' >> /etc/default/locale
ENV LANGUAGE en_US.UTF-8
ENV LANG en_US.UTF-8
ENV LC_ALL en_US.UTF-8
RUN locale-gen en_US.UTF-8
RUN dpkg-reconfigure locales
#RUN locale-gen --no-purge en_US.UTF-8 && \
#    /usr/sbin/update-locale LANG=en_US.UTF-8

# update the apt cache and install our needed packages
RUN apt-get -y update && \
    apt-get -y install postgresql postgresql-client


# Adjust PostgreSQL configuration so that remote connections to the database are possible.
RUN echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/9.1/main/pg_hba.conf
RUN echo "listen_addresses='*'" >> /etc/postgresql/9.1/main/postgresql.conf

ADD ./conf/startup.sh /opt/startup.sh


# Expose the PostgreSQL port
EXPOSE 5432

USER postgres
CMD ["bin/bash", "/opt/startup.sh"]
