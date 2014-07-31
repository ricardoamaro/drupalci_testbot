#!/bin/bash

# Remove intermediate containers after a successful build. Default is True.
DCI_REMOVEINTCONTAINERS=${DCI_REMOVEINTCONTAINERS:-"true"}

# Check if we are a member of the "docker" group or have root powers
USERNAME=$(whoami)
TEST=$(groups $USERNAME | grep -c '\bdocker\b')
if [ $TEST -eq 0 ];
then
  if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
  fi
fi

docker ps | grep "drupalci/db-mariadb-5.5" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
docker ps -a | grep "drupalci/db-mariadb-5.5" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}

docker build --rm=${DCI_REMOVEINTCONTAINERS} -t drupalci/db-mariadb-5.5 .

