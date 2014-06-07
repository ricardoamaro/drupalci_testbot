#!/bin/bash

PGVERSION=$(/usr/bin/psql --version | awk '{print $3}' | head -n1 | cut  -c 1-3)
echo "PGSQL VERSION: ${PGVERSION}"

if [ ! -z $(pg_lsclusters | grep -c ' main ') ];
    then
    echo "rebuilding PostgreSQL database cluster"
    # stop and drop the cluster
    pg_dropcluster ${PGVERSION} main --stop
    # create a fresh new cluster
    pg_createcluster ${PGVERSION} main --start

    # create a new user
    psql -c "CREATE USER drupaltestbot WITH PASSWORD 'drupaltestbotpw';"
    # create a new default database for the user
    psql -c "CREATE DATABASE drupaltestbot OWNER drupaltestbot TEMPLATE DEFAULT;"
    # stop the cluster
    pg_ctlcluster ${PGVERSION} main stop
    # allow md5-based password auth for IPv4 connections
    echo "host all all 0.0.0.0/0 md5" >> /etc/postgresql/${PGVERSION}/main/pg_hba.conf
    # listen on all addresses, not just localhost
    echo "listen_addresses='*'" >> /etc/postgresql/${PGVERSION}/main/postgresql.conf
fi

/usr/lib/postgresql/${PGVERSION}/bin/postgres -D /var/lib/postgresql/${PGVERSION}/main -c config_file=/etc/postgresql/${PGVERSION}/main/postgresql.conf
echo "pgsql died at $(date)";
