#!/bin/bash -e
#
# Name:         start_containers.sh
#
# Purpose:      Quickly start docker images
#
# Comments:
#
# Usage:        sudo ./scripts/start_containers.sh <all>
#
# Author:       Ricardo Amaro (mail_at_ricardoamaro.com)
#
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-infrastructure
#
# Docs:         README.md for complete information
#

############### SETUP DEFAULT ENV VARIABLES ###############
# Remove intermediate containers after a successful build. Default is True.
DCI_REMOVEINTCONTAINERS=${DCI_REMOVEINTCONTAINERS:-"true"}
DCI_REPODIR=${DCI_REPODIR:-"$HOME/testbotdata"}
DCI_DBVER=${DCI_DBVER:-"5.5"}
DCI_DBTYPE=${DCI_DBTYPE:-"mysql"}
DCI_DRUPALBRANCH=${DCI_DRUPALBRANCH:-"8.0.x"}
DCI_PHPVERSION=${DCI_PHPVERSION:-"5.4"}
BASEDIR="$(pwd)"
BASEIFS="${IFS}"
###########################################################


#print usage help if no arg, -h, --help
if [ "$1" = "-h" ] || [ "$1" = "--help" ];
  then
  echo
  echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/start_containers.sh <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-8.3>/<pgsql-9.1>/<pgsql-9.3><all>\e[39m "
  echo
  echo -e " Purpose:\tHelp start the testbot database containers and repos."
  echo
  echo -e "\t\t<mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-8.3>/<pgsql-9.1>/<pgsql-9.3>: Defines the database type to start. "
  echo -e "\t\tall: Start all available database containers. "
  echo -e "\t\tNote: If you are offline use 'refresh', in order to keep cached data. "
  echo
  exit 0
fi

# Check for database argument
declare -A secondarg
declare -A dbtypes
DCI_ARRKEY=0
# Check for all database containers available to build  
for constant in $(ls -d ./containers/database/*/ | awk -F/ '{print $(NF-1)}'| tr '\n' ' ');
do
  secondarg["$constant"]=1
  if [ "$1" = "$constant" ] || [ "$1" = "all" ];
  then
    dbtypes[${DCI_ARRKEY}]="$constant";
    ((DCI_ARRKEY+=1));
    if [ "$1" != "all" ];
    then
      DCI_DBTYPE=$(awk -F- '{print $1}' <<< "$1")
      DCI_DBVER=$(awk -F- '{print $2}' <<< "$1")  
    fi
  fi
done

# Default to ${DCI_DBTYPE}-${DCI_DBVER} if no database argument given
if [ ${#dbtypes[@]} -eq 0 ]; then
  dbtypes["${DCI_DBTYPE}-${DCI_DBVER}"]="${DCI_DBTYPE}-${DCI_DBVER}"
fi

if [ "$1" != "" ] && [ ${#dbtypes[@]} -eq 0 ];
  then
    echo
    echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/start_containers.sh <cleanup>/<update>/<refresh> <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-9.1>/<all>\e[39m "
    echo
    echo -e " Invalid Database type.  Please choose from mysql-5.5, mariadb-5.5, mariadb-10.0, pgsql-8.3, pgsql-9.1, pgsql-9.3, or all."
    echo
    echo -e " Example:\t\e[38;5;148msudo ./scripts/start_containers.sh refresh mysql-5.5\e[39m "
    echo
    echo -e " Usage help:\t\e[38;5;148msudo ./scripts/start_containers.sh --help\e[39m "
    echo
    exit 0
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

# Restart DB containers
for DB_BUILD in "${dbtypes[@]}";
  do
  echo
  echo "Restart db-${DB_BUILD} container"
  echo "----------------------------------------------------------------------"
  echo
  cd "./containers/database/${DB_BUILD}"
  ./stop-server.sh 2>/dev/null
  # This cleanup is specific for a single database type and is required
  # in case of a container refresh/update build.
  DCI_SQLCONT=(/tmp/tmp.*"${DB_BUILD}")
  if ( ls -d "$DCI_SQLCONT" > /dev/null 2>&1 ); then
    for DIR in "${DCI_SQLCONT[@]}"; do
      umount "${DIR}" || /bin/true
      rm -fr "${DIR}" || /bin/true
    done
  fi
  ./run-server.sh
  cd "${BASEDIR}"
done

# Set to base 
cd "${BASEDIR}"

docker ps

echo -e "Container(s) started: ${dbtypes[@]} \n"
echo -e "Example of a run:"
echo -e "sudo DCI_PATCH='/path/to/your.patch,.' DCI_CONCURRENCY="8"  DCI_TESTGROUPS='Bootstrap' DCI_DRUPALBRANCH='${DCI_DRUPALBRANCH}' DCI_UPDATEREPO="true"  DCI_DBTYPE='${DCI_DBTYPE}' DCI_DBVER='${DCI_DBVER}' DCI_PHPVERSION='${DCI_PHPVERSION}' ./containers/web/run.sh"
echo


