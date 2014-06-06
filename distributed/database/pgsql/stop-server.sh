#!/bin/bash

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

TAG="drupal/testbot-pgsql"
NAME="drupaltestbot-db-pgsql"
STALLED=$(docker ps -a | grep ${TAG} | grep Exit | awk '{print $1}')
RUNNING=$(docker ps | grep ${TAG} | grep 5432 | awk '{print $1}')

if [[ ${RUNNING} != "" ]]
  then 
    echo "Found database container: ${RUNNING} running..."
    echo "Stopping..."
    docker stop ${RUNNING}
    exit 0
  elif [[ $STALLED != "" ]]
    then
    echo "Found old container $STALLED. Removing..."
    docker rm $STALLED

    if [ -d "/tmp/tmp.*" ]; then
      rm -fr /tmp/tmp.* || /bin/true
      umount -f /tmp/tmp.* || /bin/true
      rm -fr /tmp/tmp.* || /bin/true
    fi

fi

docker rm ${NAME} 2>/dev/null || :
