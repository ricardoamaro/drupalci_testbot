#!/bin/sh

TAG="drupalci/db-mongodb-2.6"
CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')
IP=$(docker inspect --format='{{.NetworkSettings.IPAddress}}' $CONTAINER_ID)

echo $IP
mongo $IP/drupal
