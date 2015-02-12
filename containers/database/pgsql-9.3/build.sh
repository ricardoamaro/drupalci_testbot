#!/bin/bash

# Remove intermediate containers after a successful build. Default is True.
DCI_REMOVEINTCONTAINERS=${DCI_REMOVEINTCONTAINERS:-"true"}

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

docker ps | grep "drupalci/db-pgsql-9.3" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
docker ps -a | grep "drupalci/db-pgsql-9.3" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}

docker build --rm=${DCI_REMOVEINTCONTAINERS} -t drupalci/db-pgsql-9.3 .
