#!/bin/bash 

STALLED=$(docker ps -a | grep drupaltestbot-mysql | grep Exit | awk '{print $1}')
RUNNING=$(docker ps -a | grep drupaltestbot-mysql | grep 3306)
if [[ $RUNNING != "" ]]
  then 
    echo "Found container:" 
    echo "$RUNNING already running..."
    exit 0
  elif [[ $STALLED != "" ]]
    then
    echo "Found old container $STALLED. Removing..."
    docker rm $STALLED
    umount /tmp/tmp.*;
fi
  
TMPDIR=$(mktemp -d)
mount -t tmpfs -o size=16000M tmpfs $TMPDIR || exit

MYSQL_ID=$(docker run -d -p=3306:3606 --name=drupaltestbot-db -v="$TMPDIR":/var/lib/mysql drupaltestbot-mysql)
PORT=$(docker port $MYSQL_ID 3606 | cut -d":" -f2)
TAG="drupaltestbot-mysql"
CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')

echo ID : $MYSQL_ID
echo "##When done : "
echo "umount $TMPDIR"
docker ps | grep drupaltestbot-mysql

