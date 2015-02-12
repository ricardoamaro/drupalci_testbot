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

TAG="drupalci/db-pgsql-9.3"
NAME="drupaltestbot-db-pgsql-9.3"
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
    DCI_SQLCONT=(/tmp/tmp.*"pgsql-9.3")
    if ( ls -d "$DCI_SQLCONT" > /dev/null ); then
      umount -f "$DCI_SQLCONT" || /bin/true
      rm -fr "$DCI_SQLCONT" || /bin/true
    fi
fi

docker rm ${NAME} 2>/dev/null || :