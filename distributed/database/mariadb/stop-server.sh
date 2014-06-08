#!/bin/bash

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

TAG="drupal/testbot-mariadb"
NAME="drupaltestbot-db-mariadb"
STALLED=$(docker ps -a | grep ${TAG} | grep Exit | awk '{print $1}')
RUNNING=$(docker ps | grep ${TAG} | grep 3306 | awk '{print $1}')

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
    if ( ls -d /tmp/tmp.*mariadb/ ); then
      rm -fr /tmp/tmp.*mariadb || /bin/true
      umount -f /tmp/tmp.*mariadb || /bin/true
      rm -fr /tmp/tmp.*mariadb || /bin/true
    fi
fi

docker rm ${NAME} 2>/dev/null || :
