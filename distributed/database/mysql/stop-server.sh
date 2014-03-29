#!/bin/bash 

TAG="drupal/testbot-mysql"
NAME="drupaltestbot-db"
STALLED=$(docker ps -a | grep ${TAG} | grep Exit | awk '{print $1}')
RUNNING=$(docker ps | grep ${TAG} | grep 3306 | awk '{print $1}')
if [[ ${RUNNING} != "" ]]
  then 
    echo "Found database container: ${RUNNING} running..."
    echo "Stoping..."
    docker stop ${RUNNING}
    exit 0
  elif [[ $STALLED != "" ]]
    then
    echo "Found old container $STALLED. Removing..."
    docker rm $STALLED
    umount /tmp/tmp.*;
fi
