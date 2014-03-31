#!/bin/bash -e

REPODIR=${REPODIR:-"$HOME/testbotdata"}

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

curl get.docker.io | sudo sh -x

#print usage help if no arg, -h, --help
if [ "$1" = "" ] || [ "$1" = "-h" ] || [ "$1" = "--help" ]
  then
  echo -e " Usage:\t\t\e[38;5;148msudo ./build <cleanup>/<update>/<refresh> \e[39m "
  echo 
  echo -e " Purpose:\tHelp Build/rebuild/clean/update the testbot containers and repos."
  echo 
  echo -e "\t\tcleanup : Delete every docker conatiner, repos, builds and start a fresh build."
  echo -e "\t\tupdate  : Update all repos and containers." 
  echo -e "\t\trefresh : Just refresh the containers with any new change. "
  echo 
  echo -e "\t\tNote: if you are offline use 'refresh', in order to keep cached data. "
  exit 0
fi

if [ "$1" = "cleanup" ];
  then 
  set +e
  docker ps | grep drupal | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
  docker ps -a | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}
  docker images | egrep -v "debian|ubuntu|busybox" | grep -v IMAGE |  awk '{print $3}' | xargs -n1 -I {} sudo docker rmi {}
  rm -rf ${REPODIR}
  set -e 
fi


cd ./distributed/database/mysql
./stop-server.sh
./build.sh
./run-server.sh

cd ../../apachephp/
./build.sh

if [ "$1" != "refresh" ];
  then
  UPDATEREPO="true" DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
else
  DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
fi


echo "Images (re)built."
echo 'Try: sudo TESTGROUPS="User" DRUPALBRANCH="8.x" PATCH="/path/to/your.patch,." ./run.sh'

