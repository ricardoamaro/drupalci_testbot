#!/bin/bash

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

docker ps | grep "drupal/testbot-mysql" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
docker ps -a | grep "drupal/testbot-mysql" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}

docker build -t drupal/testbot-mysql --rm=true .

