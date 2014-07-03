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


TAG="drupal/testbot-mariadb"
NAME="drupaltestbot-db-mariadb"
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
    if ( ls -d /tmp/tmp.*mariadb/ ); then
      rm -fr /tmp/tmp.*mariadb || /bin/true
      umount -f /tmp/tmp.*mariadb || /bin/true
      rm -fr /tmp/tmp.*mariadb || /bin/true
    fi
fi

TMPDIR=$(mktemp -d --suffix=mariadb)
mount -t tmpfs -o size=16000M tmpfs $TMPDIR

docker run -d -p=3306 --name=${NAME} -v="$TMPDIR":/var/lib/mysql ${TAG}
CONTAINER_ID=$(docker ps | grep ${TAG} | awk '{print $1}')

#PORT=$(docker port $MYSQL_ID 3606 | cut -d":" -f2)
#TAG="drupal/testbot-mysql"

echo "CONTAINER STARTED: $CONTAINER_ID"

docker ps | grep "drupal/testbot-mariadb"

