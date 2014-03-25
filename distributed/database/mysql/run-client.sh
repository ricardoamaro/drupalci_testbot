#!/bin/sh

TAG="mysql"

CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')

#IP=$(docker inspect $CONTAINER_ID | python -c 'import json,sys;obj=json.load(sys.stdin);print obj[0]["NetworkSettings"]["IPAddress"]')
IP=$(docker inspect --format='{{.NetworkSettings.IPAddress}}' $CONTAINER_ID)

echo $IP

mysql -u drupaltestbot -p -h $IP

