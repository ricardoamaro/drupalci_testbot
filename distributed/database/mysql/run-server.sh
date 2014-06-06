#!/bin/bash

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi


TAG="drupal/testbot-mysql"
NAME="drupaltestbot-db"
STALLED=$(docker ps -a | grep ${TAG} | grep Exit | awk '{print $1}')
RUNNING=$(docker ps | grep ${TAG} | grep 3306)
if [[ $RUNNING != "" ]]
  then 
    echo "Found database container:" 
    echo "$RUNNING already running..."
    exit 0
  elif [[ $STALLED != "" ]]
    then
    echo "Found old container $STALLED. Removing..."
    docker rm $STALLED
    if ( ls -d /tmp/tmp.*mysql/ ); then
      rm -fr /tmp/tmp.*mysql || /bin/true
      umount -f /tmp/tmp.*mysql || /bin/true
      rm -fr /tmp/tmp.*mysql || /bin/true
    fi
fi
  
TMPDIR=$(mktemp -d --suffix=mysql)
mount -t tmpfs -o size=16000M tmpfs $TMPDIR

docker run -d -p=3306 --name=${NAME} -v="$TMPDIR":/var/lib/mysql ${TAG}
CONTAINER_ID=$(docker ps | grep ${TAG} | awk '{print $1}')

#PORT=$(docker port $MYSQL_ID 3606 | cut -d":" -f2)
#TAG="drupal/testbot-mysql"

echo "CONTAINER STARTED: $CONTAINER_ID"

docker ps | grep "drupal/testbot-mysql"

