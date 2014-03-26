#!/bin/bash -e

TMPDIR=$(mktemp -d)
sudo mount -t tmpfs -o size=16000M tmpfs $TMPDIR || exit

MYSQL_ID=$(docker run -d -p=3306:3606 --name=drupaltestbot-db -v="$TMPDIR":/var/lib/mysql drupaltestbot-mysql)
PORT=$(docker port $MYSQL_ID 3606 | cut -d":" -f2)

TAG="drupaltestbot-mysql"

CONTAINER_ID=$(docker ps | grep $TAG | awk '{print $1}')

echo ID : $MYSQL_ID

echo "##Container ID : "
echo $CONTAINER_ID

echo "##Check the logs : "
echo "docker logs $MYSQL_ID"

echo "##When done : "
echo "docker stop $MYSQL_ID"
echo "docker rm $MYSQL_ID"
echo "umount $TMPDIR"

docker ps

