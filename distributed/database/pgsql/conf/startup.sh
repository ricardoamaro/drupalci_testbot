#!/bin/bash

export LANGUAGE=en_US.UTF-8
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

if [ ! -z $(pg_lsclusters | grep -c ' main ') ]; 
    then
    echo "rebuilding PostgreSQL database cluster"
    # stop and drop the cluster
    pg_dropcluster 9.1 main --stop
    # create a fresh new cluster
    pg_createcluster 9.1 main --start

    # create a new user
    psql -c "CREATE USER drupaltestbot WITH PASSWORD 'drupaltestbotpw' CREATEDB;"
    # create a new default database for the user
    psql -c "CREATE DATABASE drupaltestbot OWNER drupaltestbot TEMPLATE DEFAULT ENCODING='utf8' LC_CTYPE='en_US.UTF-8' LC_COLLATE='en_US.UTF-8';"
    # stop the cluster
    pg_ctlcluster 9.1 main stop
    # allow md5-based password auth for IPv4 connections
    echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/9.1/main/pg_hba.conf
    # listen on all addresses, not just localhost
    echo "listen_addresses='*'" >> /etc/postgresql/9.1/main/postgresql.conf
fi

/usr/lib/postgresql/9.1/bin/postgres -D /var/lib/postgresql/9.1/main -c config_file=/etc/postgresql/9.1/main/postgresql.conf
echo "pgsql died at $(date)";
