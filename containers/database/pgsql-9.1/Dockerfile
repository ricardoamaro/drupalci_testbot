FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN apt-get -qq update
RUN apt-get -qq -y install locales debconf

# Set a default language, so that PostgreSQL can use UTF-8 encoding (required)
RUN echo 'LANG="en_US.UTF-8"' > /etc/default/locale
RUN echo 'LANGUAGE="en_US:en"' >> /etc/default/locale

# update the apt cache and install our needed packages
RUN apt-get -y update && \
    apt-get -y install postgresql postgresql-client

RUN apt-get clean
RUN apt-get autoclean
RUN apt-get -y autoremove

# Adjust PostgreSQL configuration so that remote connections to the database are possible.
RUN echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/9.1/main/pg_hba.conf
RUN echo "listen_addresses='*'" >> /etc/postgresql/9.1/main/postgresql.conf

# Expose the PostgreSQL port
EXPOSE 5432

ENV LANGUAGE en_US.UTF-8
ENV LANG en_US.UTF-8
ENV LC_ALL en_US.UTF-8

ADD conf/locale.gen /etc/locale.gen

RUN locale-gen en_US.UTF-8
RUN dpkg-reconfigure locales
#RUN locale-gen --no-purge en_US.UTF-8 && \
#    /usr/sbin/update-locale LANG=en_US.UTF-8

USER postgres

ADD ./conf/startup.sh /opt/startup.sh

CMD ["/bin/bash", "/opt/startup.sh"]
