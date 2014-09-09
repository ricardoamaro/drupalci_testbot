FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN apt-get -qq update
RUN apt-get -qq -y install locales debconf wget
RUN apt-get -y install python-software-properties && \
    add-apt-repository 'deb http://apt.postgresql.org/pub/repos/apt/ wheezy-pgdg main'
    
RUN apt-get autoclean
RUN apt-get autoremove

# Set a default language, so that PostgreSQL can use UTF-8 encoding (required)
RUN localedef -i en_US -c -f UTF-8 en_US.UTF-8
RUN echo 'LANG="en_US.UTF-8"' > /etc/default/locale
RUN echo 'LC_ALL="en_US.UTF-8"' > /etc/default/locale
RUN echo 'LANGUAGE="en_US:en"' >> /etc/default/locale

ADD conf/locale.gen /etc/locale.gen

RUN locale-gen en_US.UTF-8
RUN dpkg-reconfigure locales

ENV LANGUAGE en_US:en
ENV LANG en_US.UTF-8
ENV LC_ALL en_US.UTF-8

# update the apt cache and install our needed packages
RUN wget -O - http://apt.postgresql.org/pub/repos/apt/ACCC4CF8.asc | apt-key add -
RUN apt-get -y update && \
    apt-get -y install postgresql-8.3

# Adjust PostgreSQL configuration so that remote connections to the database are possible.
RUN echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/8.3/main/pg_hba.conf
RUN echo "listen_addresses='*'" >> /etc/postgresql/8.3/main/postgresql.conf

# Expose the PostgreSQL port
EXPOSE 5432

USER postgres

ADD ./conf/startup.sh /opt/startup.sh

CMD ["bin/bash", "/opt/startup.sh"]
