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

declare -A firstarg
for constant in cleanup update refresh
do
  firstarg[$constant]=1
done

#print usage help if no arg, -h, --help
if [ "$1" = "" ] || [ "$1" = "-h" ] || [ "$1" = "--help" ] || [[ ! ${firstarg[$1]} ]];
  then
  echo
  echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/build_all.sh <cleanup>/<update>/<refresh> <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-9.1>/<pgsql-9.4>/<all>\e[39m "
  echo
  echo -e " Purpose:\tHelp Build/rebuild/clean/update the testbot containers and repos."
  echo
  echo -e "\t\tcleanup : Delete every docker container, repos, builds and start a fresh build."
  echo -e "\t\tupdate  : Update all repos and containers."
  echo -e "\t\trefresh : Just refresh the containers with any new change. "
  echo
  echo -e "\t\tmysql/pgsql/mariadb: Defines the database type(s) to build. "
  echo -e "\t\tall: Builds all available database + web containers. "
  echo -e "\t\tNote: If you are offline use 'refresh', in order to keep cached data. "
  echo
  exit 0
fi

# Build all the base containers
BASECONTAINERS=$(ls -d ./containers/base/*/ | awk -F/ '{print $(NF-1)}'| tr '\n' ' ');

# Build all webcontainers present on web directory or only the default
case "$2" in
  all)
    WEBCONTAINERS=$(ls -d ./containers/web/*/ | awk -F/ '{print $(NF-1)}'| tr '\n' ' ');;
  *)
    WEBCONTAINERS="web-${DCI_PHPVERSION}";;
esac

# Check for database argument
declare -A secondarg
declare -A dbtypes
DCI_ARRKEY=0
# Check for all database containers available to build
for constant in $(ls -d ./containers/database/*/ | awk -F/ '{print $(NF-1)}'| tr '\n' ' ');
do
  secondarg["$constant"]=1
  if [ "$2" = "$constant" ] || [ "$2" = "all" ];
  then
    dbtypes[${DCI_ARRKEY}]="$constant";
    ((DCI_ARRKEY+=1));
    if [ "$2" != "all" ];
    then
      DCI_DBTYPE=$(awk -F- '{print $1}' <<< "$2")
      DCI_DBVER=$(awk -F- '{print $2}' <<< "$2")
    fi
  fi
done

# Default to ${DCI_DBTYPE}-${DCI_DBVER} if no database argument given
if [ ${#dbtypes[@]} -eq 0 ]; then
  dbtypes["${DCI_DBTYPE}-${DCI_DBVER}"]="${DCI_DBTYPE}-${DCI_DBVER}"
fi

if [ "$2" != "" ] && [ ${#dbtypes[@]} -eq 0 ];
  then
    echo
    echo -e " Usage:\t\t\e[38;5;148msudo ./scripts/build_all.sh <cleanup>/<update>/<refresh> <mysql-5.5>/<mariadb-5.5>/<mariadb-10.0>/<pgsql-9.1>/<all>\e[39m "
    echo
    echo -e " Invalid Database type.  Please choose from mysql-5.5, mariadb-5.5, mariadb-10.0, pgsql-9.1, pgsql-9.4, or all."
    echo
    echo -e " Example:\t\e[38;5;148msudo ./scripts/build_all.sh refresh mysql-5.5\e[39m "
    echo
    echo -e " Usage help:\t\e[38;5;148msudo ./scripts/build_all.sh --help\e[39m "
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
  if [ -z $(docker version | grep "Server version" | awk '{print $3}') ];
    then
    DOCKER_VERSION=$(docker version --format '{{.Server.Version}}')
    else
    DOCKER_VERSION=$(docker version | grep "Server version" | awk '{print $3}')
  fi
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
  docker ps -a | awk '{print $1}' | grep -v CONTAINER | xargs -n1 -I {} docker rm -f {}
  docker images | egrep "drupal|testbot|none" | grep -v IMAGE |  awk '{print $3}' | xargs -n1 -I {} docker rmi -f {}
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

echo
echo "Make sure we build the base containers"

IFS="${BASEIFS}"
for BASEDIRS in ${BASECONTAINERS};
  do
  echo
  echo "Building BASE ${BASEDIRS} container"
  echo "----------------------------------------------------------------------"
  cd "${BASEDIR}"
  cd "./containers/base/${BASEDIRS}"
  ./build.sh
done


# Build and start DB containers
for DB_BUILD in "${dbtypes[@]}";
  do
  echo
  echo "Build and restart db-${DB_BUILD} container"
  echo "----------------------------------------------------------------------"
  echo
  cd "${BASEDIR}"
  cd "./containers/database/${DB_BUILD}"
  ./stop-server.sh
  # This cleanup is specific for a single database type and is required
  # in case of a container refresh/update build.
  DCI_SQLCONT=(/tmp/tmp.*"${DB_BUILD}")
  if ( ls -d "$DCI_SQLCONT" > /dev/null 2>&1 ); then
    for DIR in "${DCI_SQLCONT[@]}"; do
      umount "${DIR}" || /bin/true
      rm -fr "${DIR}" || /bin/true
    done
  fi
  ./build.sh
  ./run-server.sh
  cd "${BASEDIR}"
done

IFS="${BASEIFS}"
for WEBDIR in ${WEBCONTAINERS};
  do
  echo
  echo "Building PHP ${WEBDIR} container"
  echo "----------------------------------------------------------------------"
  cd "${BASEDIR}"
  cd "./containers/web/${WEBDIR}"
  ./build.sh
done
echo "----------------------------------------------------------------------"
echo -e "\tContainer images (re)built: \nBASE:\t${BASECONTAINERS}\n  DB:\t${dbtypes[@]} \n WEB:\t${WEBCONTAINERS}\n"

# Set to base
cd "${BASEDIR}"
# Do a test run to collect test list and update repos
#if [ "$1" != "refresh" ];
#  then
#  sleep 3
#  DCI_DBTYPE=${DCI_DBTYPE} DCI_DBVER=${DCI_DBVER} DCI_UPDATEREPO="true" DCI_DRUPALBRANCH=${DCI_DRUPALBRANCH} DCI_PHPVERSION=${DCI_PHPVERSION} DCI_RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./containers/web/run.sh
#else
#  sleep 3
#  DCI_DBTYPE=${DCI_DBTYPE} DCI_DBVER=${DCI_DBVER} DCI_DRUPALBRANCH=${DCI_DRUPALBRANCH} DCI_PHPVERSION=${DCI_PHPVERSION} DCI_RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" ./containers/web/run.sh
#fi

echo -e "Container Images: ${dbtypes[@]} and ${WEBCONTAINERS} (re)built.\n"
echo -e "Try example: sudo DCI_DBTYPE='${DCI_DBTYPE}' DCI_DBVER='${DCI_DBVER}' DCI_PHPVERSION='${DCI_PHPVERSION}' DCI_TESTGROUPS='Bootstrap' DCI_DRUPALBRANCH='${DCI_DRUPALBRANCH}' DCI_PATCH='/path/to/your.patch,.' ./containers/web/run.sh"
