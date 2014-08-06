#!/bin/bash -e
#
# Name:         build_all.sh
#
# Purpose:      Quickly build/update/refresh of all docker images
#
# Comments:
#
# Usage:        sudo ./scripts/build_all.sh <cleanup>/<update>/<refresh>
#
# Author:       Ricardo Amaro (mail_at_ricardoamaro.com)
# Contributors: Jeremy Thorson jthorson
#
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-infrastructure
#
# Docs:         README.md for complete information
#

# Remove intermediate containers after a successful build. Default is True.
DCI_REMOVEINTCONTAINERS=${DCI_REMOVEINTCONTAINERS:-"true"}
DCI_REPODIR=${DCI_REPODIR:-"$HOME/testbotdata"}
BASEDIR="$(pwd)"

declare -A firstarg
for constant in cleanup update refresh
do
  firstarg[$constant]=1
done

#print usage help if no arg, -h, --help
if [ "$1" = "" ] || [ "$1" = "-h" ] || [ "$1" = "--help" ] || [[ ! ${firstarg[$1]} ]];
  then
  echo
  echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/build_all.sh <cleanup>/<update>/<refresh> <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-8.3>/<pgsql-9.1>/<all>\e[39m "
  echo
  echo -e " Purpose:\tHelp Build/rebuild/clean/update the testbot containers and repos."
  echo
  echo -e "\t\tcleanup : Delete every docker container, repos, builds and start a fresh build."
  echo -e "\t\tupdate  : Update all repos and containers."
  echo -e "\t\trefresh : Just refresh the containers with any new change. "
  echo
  echo -e "\t\tmysql/pgsql/mariadb/all : Defines the database type(s) to build. "
  echo -e "\t\tNote: If you are offline use 'refresh', in order to keep cached data. "
  echo
  exit 0
fi

# Check for database argument
declare -A secondarg
declare -A dbtypes
# Make sure mysql-5.5 is defined as last target in loop to make it default in
# case script was called with the <all> parameter. In addition we need a
# numeric array key to keep order.
DCI_ARRKEY=0
for constant in mariadb-5.5 mariadb-10.0 pgsql-8.3 pgsql-9.1 mysql-5.5
do
  secondarg["$constant"]=1
  if [ "$2" = "$constant" ] || [ "$2" = "all" ];
  then
    dbtypes[${DCI_ARRKEY}]="$constant"
    ((DCI_ARRKEY+=1))
  fi
done

if [ "$2" != "" ] && [ ${#dbtypes[@]} -eq 0 ];
  then
    echo
    echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/build_all.sh <cleanup>/<update>/<refresh> <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-9.1>/<all>\e[39m "
    echo
    echo -e " Invalid Database type.  Please choose from mysql-5.5, mariadb-5.5, mariadb-10.0, pgsql-8.3, pgsql-9.1, or all."
    echo
    echo -e " Example:\t\e[38;5;148msudo ./scripts/build_all.sh refresh mysql-5.5\e[39m "
    echo
    echo -e " Usage help:\t\e[38;5;148msudo ./scripts/build_all.sh --help\e[39m "
    echo
    exit 0
fi

# Default to mysql-5.5 if no database argument given
if [ ${#dbtypes[@]} -eq 0 ]; then
  dbtypes["mysql-5.5"]="mysql-5.5"
fi

# Check if we have root powers
if [ `whoami` != root ]; then
  echo "Please run this script as root or using sudo"
  exit 1
fi

# Check if curl is installed
command -v curl >/dev/null 2>&1 || { echo >&2 "Command 'curl' is required. Please install it and run again. Aborting."; exit 1; }

# Make sure we are at the root
cd "${BASEDIR}"

# Check for PHP
set +e
if [ ! -f /usr/bin/php ];
  then
  echo
  echo "Failed to detect PHP."
  echo "Please make sure PHP is installed and is >= 5.3."
  echo "--------------------------------------------------------------"
  exit 1
  else
  # Check PHP Version
  PHP_VERSION=`/usr/bin/php -r 'echo phpversion();'`
  if [ -z ${PHP_VERSION} ];
    then
    echo
    echo "Failed to detect PHP version."
    echo "Please make sure PHP is installed and is >= 5.3."
    echo "----------------------------------------------------------------------"
    exit 1
  fi
  IFS=. components=(${PHP_VERSION})
  if [ ${components[0]} -ge 5 ] && [ ${components[1]} -ge 3 ];
    then
    echo
    echo "PHP version ${PHP_VERSION} found at /usr/bin/php"
    echo "----------------------------------------------------------------------"
    else
    echo
    echo "PHP version ${PHP_VERSION} found at /usr/bin/php:"
    echo "Your installed PHP version is too old! Upgrade to >= 5.3."
    echo "----------------------------------------------------------------------"
    exit 1
  fi
fi

# Check for Docker
set +e
if [ ! -f /usr/bin/docker ];
  then
  echo
  echo "Failed to detect Docker."
  echo "Please make sure Docker is installed and configured correctly."
  echo "Visit: https://docs.docker.com/installation/ for further instructions"
  echo "Or try to use the available installers on the scripts directory."
  echo "----------------------------------------------------------------------"
  exit 1
  else
  echo
  # Check Docker Version
  DOCKER_VERSION=$(docker version | grep "Server version" | awk '{print $3}')
  if [ -z "${DOCKER_VERSION}" ];
    then
    echo
    echo "Failed to detect Docker Version."
    echo "Please make sure Docker is installed and configured correctly."
    echo "----------------------------------------------------------------------"
    exit 1
  fi
  IFS=. components=(${DOCKER_VERSION})
  if [ ${components[0]} -ge 1 ] && [ ${components[1]} -ge 0 ];
    then
    echo "Docker Version ${DOCKER_VERSION} found at /usr/bin/docker"
    echo "----------------------------------------------------------------------"
    else
    echo
    echo "Docker Version ${DOCKER_VERSION} found at /usr/bin/docker:"
    echo "Your installed Docker Version is to old!"
    echo "Docker Version >= 1.0 is required. Please visit:"
    echo "http://www.docker.com for instructions how to upgrade to latest release."
    echo "------------------------------------------------------------------------"
    exit 1
  fi
fi

# Clean all images per request
if [ "$1" = "cleanup" ];
  then
  echo
  echo "stop and remove testbot containers and images"
  echo "----------------------------------------------------------------------"
  echo
  docker ps | egrep "drupal|test" | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} docker stop {}
  docker ps -a | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} docker rm {}
  docker images | egrep "drupal|testbot|none" | grep -v IMAGE |  awk '{print $3}' | xargs -n1 -I {} docker rmi {}
  rm -rf ${DCI_REPODIR}
  # Just umount and remove all and everything in /tmp/tmp.*
  DCI_SQLCONT=(/tmp/tmp.*)
  if ( ls -d "$DCI_SQLCONT" > /dev/null ); then
    for DIR in "${DCI_SQLCONT[@]}"; do
      umount "${DIR}" || /bin/true
      rm -fr "${DIR}" || /bin/true
    done
    unset DCI_SQLCOUNT
  fi

fi
set -e

# Build and start DB containers
for DCI_DBTYPE in "${dbtypes[@]}";
  do
  echo
  echo "Build and restart db-${DCI_DBTYPE} container"
  echo "----------------------------------------------------------------------"
  echo
  cd "./containers/database/${DCI_DBTYPE}"
  ./stop-server.sh
  # This cleanup is specific for a single database type and is required
  # in case of a container refresh/update build.
  DCI_SQLCONT=(/tmp/tmp.*"${DCI_DBTYPE}")
  if ( ls -d "$DCI_SQLCONT" > /dev/null ); then
    for DIR in "${DCI_SQLCONT[@]}"; do
      umount "${DIR}" || /bin/true
      rm -fr "${DIR}" || /bin/true
    done
  fi
  ./build.sh
  ./run-server.sh
  cd "${BASEDIR}"

  # Set up DB container arguments for run script
  IFS="-" components=($DCI_DBTYPE)
  DCI_DBVER=${components[1]}
  DCI_DBTYPE=${components[0]}
done

echo
echo "Make sure we build web containers"
echo "----------------------------------------------------------------------"
echo
cd ./containers/web/
./build.sh
cd "${BASEDIR}"

echo -e "Container Images: ${dbtypes[@]} and web-5.4 (re)built.\n"

# Do a test run to collect test list and update repos
if [ "$1" != "refresh" ];
  then
  sleep 5
  DCI_DBTYPE=${DCI_DBTYPE} DCI_DBVER=${DCI_DBVER} DCI_UPDATEREPO="true" DCI_DRUPALBRANCH="8.0.x" DCI_RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./containers/web/run.sh
else
  sleep 5
  DCI_DBTYPE=${DCI_DBTYPE} DCI_DBVER=${DCI_DBVER} DCI_DRUPALBRANCH="8.0.x" DCI_RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./containers/web/run.sh
fi

echo -e "Container Images: ${dbtypes[@]} and web-5.4 (re)built.\n"
echo -e "Try example: sudo DCI_DBTYPE='${DCI_DBTYPE}' DCI_DBVER='${DCI_DBVER}' DCI_PHPVERSION='5.4' DCI_TESTGROUPS='Bootstrap' DCI_DRUPALBRANCH='8.0.x' DCI_PATCH='/path/to/your.patch,.' ./containers/web/run.sh"

