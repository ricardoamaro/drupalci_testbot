#!/bin/bash

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

docker ps | grep "drupal/testbot-pgsql" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
docker ps -a | grep "drupal/testbot-pgsql" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}

docker build -t drupal/testbot-pgsql --rm=true .

