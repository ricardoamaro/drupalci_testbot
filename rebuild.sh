#!/bin/bash -e

REPODIR=${REPODIR:-"$HOME/testbotdata"}

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

#add usage help if no arg
if [ "$1" = "" ]
  then
  echo -e " Usage:\t\t\e[38;5;148msudo ./rebuild <cleanup>/<update>/<refresh> \e[39m "
  echo 
  echo -e " Purpose:\tHelp Rebuild/clean/update the testbot containers and repos."
  echo -e "\t\tcleanup: Delete every docker conatiner, repos, builds and start a fresh build."
  echo -e "\t\tupdate: Update all repos and containers." 
  echo -e "\t\trefresh: Just refresh the containers with any new change. "
  echo 
  echo -e "\t\tNote: if you are offline use 'refresh', in order to keep cached data. "
  exit 1
fi

if [ "$1" = "cleanup" ];
  then 
  for IMAGE in drupal/testbot-mysql drupal/testbot-web ; do
  docker images | grep "${IMAGE}" | awk '{print $3}' | xargs -n1 -I {} sudo docker rm {}
  done
  rm -rf ${REPODIR}
fi


cd ./distributed/database/mysql
./stop-server.sh
./build.sh

cd ../../apachephp/
./build.sh

if [ "$1" != "refresh" ];
  then
  UPDATEREPO="true" DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
else
  DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
fi


echo "Images rebuilt."
echo 'Try: sudo TESTGROUPS="User" DRUPALBRANCH="8.x" PATCH="/path/to/your.patch,." ./run.sh'
