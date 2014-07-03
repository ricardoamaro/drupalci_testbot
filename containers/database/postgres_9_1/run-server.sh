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


TAG="drupal/testbot-pgsql"
NAME="drupaltestbot-db-pgsql"
STALLED=$(docker ps -a | grep ${TAG} | grep Exit | awk '{print $1}')
echo jsmith Stalled $STALLED
RUNNING=$(docker ps | grep ${TAG} | grep 5432)
echo jsmith Running $RUNNING
if [[ $RUNNING != "" ]]
  then 
    echo "Found database container:" 
    echo "$RUNNING already running..."
    exit 0
  elif [[ $STALLED != "" ]]
    then
    echo "Found old container $STALLED. Removing..."
    docker rm $STALLED
    if ( ls -d /tmp/tmp.*pgsql/ ); then
      rm -fr /tmp/tmp.*pgsql || /bin/true
      umount -f /tmp/tmp.*pgsql || /bin/true
      rm -fr /tmp/tmp.*pgsql || /bin/true
    fi
fi
  
TMPDIR=$(mktemp -d --suffix=pgsql)
mount -t tmpfs -o size=16000M tmpfs $TMPDIR

docker run -d -p=5432 --name=${NAME} -v="$TMPDIR":/var/lib/postgresql ${TAG}
CONTAINER_ID=$(docker ps | grep ${TAG} | awk '{print $1}')

#PORT=$(docker port $MYSQL_ID 5432 | cut -d":" -f2)
#TAG="drupal/testbot-pgsql"

echo "CONTAINER STARTED: $CONTAINER_ID"

docker ps | grep "drupal/testbot-pgsql"

