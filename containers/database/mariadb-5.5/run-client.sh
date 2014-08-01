#!/bin/sh

TAG="drupalci/db-mariadb-5.5"
CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')
IP=$(docker inspect --format='{{.NetworkSettings.IPAddress}}' $CONTAINER_ID)

echo $IP
mysql -u drupaltestbot -pdrupaltestbotpw -h $IP
