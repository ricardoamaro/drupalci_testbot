#!/bin/bash -e
#
# Name:         build_all.sh
#
# Purpose:      Quickly build/update/refresh of all docker images 
#
# Comments:          
#
# Usage:        sudo ./build_all.sh <cleanup>/<update>/<refresh>
#
# Author:       Ricardo Amaro (mail@ricardoamaro.com)
# Contributors: Jeremy Thorson jthorson
#           
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-infrastructure
# 
# Docs:         README.md for complete information
#

REPODIR=${REPODIR:-"$HOME/testbotdata"}
PWD="$(pwd)"

#print usage help if no arg, -h, --help
if [ "$1" = "" ] || [ "$1" = "-h" ] || [ "$1" = "--help" ]
  then
  echo 
  echo -e " Usage:\t\t\e[38;5;148msudo ./build_all.sh <cleanup>/<update>/<refresh> \e[39m "
  echo 
  echo -e " Purpose:\tHelp Build/rebuild/clean/update the testbot containers and repos."
  echo 
  echo -e "\t\tcleanup : Delete every docker container, repos, builds and start a fresh build."
  echo -e "\t\tupdate  : Update all repos and containers." 
  echo -e "\t\trefresh : Just refresh the containers with any new change. "
  echo 
  echo -e "\t\tNote: If you are offline use 'refresh', in order to keep cached data. "
  echo
  exit 0
fi

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

# Make sure we are at the root 
cd ${PWD}

# Install Docker
set +e 
if [ ! -f /usr/bin/docker ];
    then 
    echo
    echo "Installing Docker from get.docker.io"
    echo "------------------------------------"
    echo 
    curl -s get.docker.io | sh 2>&1 | egrep -i -v "Ctrl|docker installed"
    else 
    echo
    echo "Docker found at /usr/bin/docker:"
    echo "------------------------------------"
    docker version
fi

# Clean all images per request
if [ "$1" = "cleanup" ];
  then 
  echo
  echo "stop and remove testbot containers and images"
  echo "---------------------------------------------"
  echo
  docker ps | grep drupal | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker stop {}
  docker ps -a | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} sudo docker rm {}
  docker images | egrep "testbot|none" | grep -v IMAGE |  awk '{print $3}' | xargs -n1 -I {} sudo docker rmi {}
  rm -rf ${REPODIR}
fi
set -e

# Build and start containers
echo
echo "Build and start mysql container"
echo "------------------------------------"
echo
cd ./distributed/database/mysql
./stop-server.sh
umount /tmp/tmp.* >/dev/null || /bin/true
rm -rf /tmp/tmp.* >/dev/null || /bin/true
./build.sh
./run-server.sh


echo 
echo "Make sure we Build web containers"
echo "------------------------------------"
echo
cd ../../apachephp/
./build.sh

# Do a test run to collect test list and update repos 
if [ "$1" != "refresh" ];
  then
  sleep 5
  UPDATEREPO="true" DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
else
  sleep 5
  DRUPALBRANCH="8.x" RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./run.sh
fi

echo "Images (re)built."
echo 'Try example: sudo TESTGROUPS="Bootstrap" DRUPALBRANCH="8.x" PATCH="/path/to/your.patch,." ./run.sh'

