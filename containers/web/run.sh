#!/bin/bash -e
#
# Name:         run.sh
#
# Purpose:      Start the testbot build runs
#
# Comments:
#
# Usage:        sudo {VARIABLES} ./run.sh
#
# Author:       Ricardo Amaro (mail_at_ricardoamaro.com)
# Contributors: Jeremy Thorson jthorson
#
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-infrastructure
#
# Docs:         README.md for complete information
#

#print usage help if -h, --help
if [ "$1" = "-h" ] || [ "$1" = "--help" ]
  then
  echo -e "\n"
  echo -e "Usage:\t\t\e[38;5;148msudo {VARIABLES} ./run.sh\e[39m "
  echo -e "Purpose:\tRun the testbot containers and do tests."
  echo
  echo "....................... VARIABLES .........................."
  echo -e "
PATCH:         Local or remote Patches to be applied.
               Format: patch_location,apply_dir;patch_location,apply_dir;...
DCI_DEPENDENCIES:  Contrib projects to be downloaded & patched.
               Format: module1,module2,module2...
DCI_DEPENDENCIES_GIT  Format: gitrepo1,branch;gitrepo2,branch;...
DCI_DEPENDENCIES_TGZ  Format: module1_url.tgz,module1_url.tgz,...
DRUPALBRANCH:  Default is '8.0.x'
DRUPALVERSION: Default is '8'
TESTGROUPS:    Tests to run. Default is '--class NonDefaultBlockAdmin'
               A list is available at the root of this project.
VERBOSE:       Default is 'false'
DBTYPE:        Default is 'mysql-5.5' from mysql/sqlite/pgsql
DBVER:         Default is '5.5'.  Used to override the default version for a given database type.
CMD:           Default is none. Normally use '/bin/bash' to debug the container
INSTALLER:     Default is none. Try to use core non install tests.
DCI_UPDATEREPO:    Force git pull of Drupal & Drush. Default is 'false'
DCI_IDENTIFIER:    Automated Build Identifier. Only [a-z0-9-_.] are allowed
DCI_REPODIR:       Default is 'HOME/testbotdata'
DCI_DRUPALREPO:    Default is 'http://git.drupal.org/project/drupal.git'
DCI_DRUSHREPO:     Default is 'https://github.com/drush-ops/drush.git'
BUILDSDIR:     Default is  equal to DCI_REPODIR
WORKSPACE:     Default is 'HOME/testbotdata/DCI_IDENTIFIER/'
DBUSER:        Default is 'drupaltestbot'
DBPASS:        Default is 'drupaltestbotpw'
DBCONTAINER:   Default is 'drupaltestbot-db-mysql-5.5'
PHPVERSION:    Default is '5.4'
CONCURRENCY:   Default is '4'  #How many cpus to use per run
RUNSCRIPT:     Default is 'php  RUNNER  --php /usr/bin/php --url 'http://localhost' --color --concurrency  CONCURRENCY  --verbose --xml '/var/workspace/results'  TESTGROUPS  | tee /var/www/test.stdout ' "
echo -e "\n\nExamples:\t\e[38;5;148msudo {VARIABLES} ./run.sh\e[39m "
echo -e "
Run Action and Node tests, 2 LOCAL patches, using 4 CPUs, against D8:
.....................................................................
sudo TESTGROUPS=\"Action,Node\" CURRENCY=\"4\" DRUPALBRANCH=\"8.0.x\"  PATCH=\"/tmp/1942178-config-schema-user-28.patch,.;/tmp/1942178-config-schema-30.patch,.\" ./run.sh

Run all tests using 4 CPUs, 1 core patch against D8:
.....................................................................
sudo TESTGROUPS=\"--all\" CONCURRENCY=\"4\" DRUPALBRANCH=\"8.0.x\" PATCH=\"https://drupal.org/files/issues/1942178-config-schema-user-28.patch,.\" ./run.sh
"
  exit 0
fi

# A list of variables that we only set if empty. Export them before running the script.
# Note: Any variable already set on a higher level will keep it's value.

DCI_IDENTIFIER=${DCI_IDENTIFIER:-"build_$(date +%Y_%m_%d_%H%M%S)"}
DRUPALBRANCH=${DRUPALBRANCH:-"8.0.x"}
DRUPALVERSION=${DRUPALVERSION:-"$(echo $DRUPALBRANCH | awk -F. '{print $1}')"}
DCI_UPDATEREPO=${DCI_UPDATEREPO:-"false"}
DCI_REPODIR=${DCI_REPODIR:-"$HOME/testbotdata"}
DCI_DRUPALREPO=${DCI_DRUPALREPO:-"http://git.drupal.org/project/drupal.git"}
DCI_DRUSHREPO=${DCI_DRUSHREPO:-"https://github.com/drush-ops/drush.git"}
BUILDSDIR=${BUILDSDIR:-"$DCI_REPODIR"}
WORKSPACE=${WORKSPACE:-"$BUILDSDIR/$DCI_IDENTIFIER/"}
DCI_DEPENDENCIES=${DCI_DEPENDENCIES:-""}
DCI_DEPENDENCIES_GIT=${DCI_DEPENDENCIES_GIT:-""}
DCI_DEPENDENCIES_TGZ=${DCI_DEPENDENCIES_TGZ:-""}  #TODO
PATCH=${PATCH:-""}
DBUSER=${DBUSER:-"drupaltestbot"}
DBPASS=${DBPASS:-"drupaltestbotpw"}
DBTYPE=${DBTYPE:-"mysql"} #mysql/pgsql/sqlite
DBVER=${DBVER:-"5.5"}
CMD=${CMD:-""}
INSTALLER=${INSTALLER:-"none"}
VERBOSE=${VERBOSE:-"false"}
PHPVERSION=${PHPVERSION:-"5.4"}
CONCURRENCY=${CONCURRENCY:-"4"} #How many cpus to use per run
TESTGROUPS=${TESTGROUPS:-"Bootstrap"} #TESTS TO RUN from https://api.drupal.org/api/drupal/classes/8

# run-tests.sh place changes on 8.0.x
case $DRUPALVERSION in
  8) RUNNER="./core/scripts/run-tests.sh"
     MODULESPATH="./modules"
    ;;
  *) RUNNER="./scripts/run-tests.sh"
     MODULESPATH="./sites/all/modules"
    ;;
esac

case $DBTYPE in
  pgsql)
     if [ -z ${DBVER+x} ];
       then
         DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-pgsql-9.1"}
       else
         case $DBVER in
           8.3)  DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-pgsql-8.3"}
           ;;
           9.1)  DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-pgsql-9.1"}
           ;;
         esac
     fi
     DBPORT="5432"
  ;;
  mariadb)
    if [ -z ${DBVER+x} ];
      then
        DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-mariadb-5.5"}
      else
        case $DBVER in
          5.5)  DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-mariadb-5.5"}
          ;;
          10.0)   DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-mariadb-10.0"}
          ;;
        esac
    fi
    DBPORT="3306"
  ;;
  mysql | *)
    DBPORT="3306"
    DBCONTAINER=${DBCONTAINER:-"drupaltestbot-db-mysql-5.5"}
  ;;
esac
DBLINK=${DBLINK:-"--link=${DBCONTAINER}:db"}

case $VERBOSE in
  true) VERBO="--verbose"
    ;;
  *) VERBO=""
    ;;
esac

RUNSCRIPT=${RUNSCRIPT:-"php ${RUNNER} --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} ${VERBO} --xml '/var/workspace/results'"}

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

mkdir -p ${DCI_REPODIR}

# Check if we have free disk space
FREEDISK=$(df -m ${BUILDSDIR} | tail -n1 | awk '{print $4}')
if (( $FREEDISK <= 100 ));
  then
    echo ""
    echo "ERROR! Low disk space!";
    echo ""
    df -hT ${BUILDSDIR}
    echo ""
    echo "Try to clean up some disk space from ${BUILDSDIR}"
    echo "A minimum of 100MB is required..."
    exit 1;
fi

# If we are using a non-sqlite database, make sure the container is there
if [[ $DBTYPE != "sqlite" ]]
  then
    set +e
    RUNNING=$(sudo docker ps | grep ${DBCONTAINER} | grep -s ${DBPORT})
    set -e
    if [[ $RUNNING = "" ]]
      then
        echo "--------------------------------------------------------------------------------"
        echo -e "ERROR: There is no ${DBCONTAINER} container running..."
        echo -e "Please make sure you built the image and started it:"
        echo -e "sudo ./scripts/build_all.sh refresh \n"
        echo -e "Also please make sure port ${DBPORT} is not being used \nand ${DBTYPE} is stopped on the host."
        echo "--------------------------------------------------------------------------------"
        exit 1
    fi
fi

#Ensure PHPVERSION is set
case $PHPVERSION in
  5.3)
    PHPVERSION="5.3"
    ;;
  5.5)
    PHPVERSION="5.5"
    ;;
  *)
    PHPVERSION="5.4"
    ;;
esac

#Check if the web container is built
if $(docker images | grep -q web-${PHPVERSION});
  then
  echo "--------------------------------------------------------------------------------"
  echo "Containers: web-${PHPVERSION} and ${DBCONTAINER} available"
  echo "Running PHP${PHPVERSION}/${DBTYPE} on drupalci/web-${PHPVERSION}"
  echo "--------------------------------------------------------------------------------"
  else
  echo "--------------------------------------------------------------------------------"
  echo "ERROR. Image drupalci/web-${PHPVERSION} needs to be built with:"
  echo "sudo ./build.sh ${PHPVERSION}"
  echo "--------------------------------------------------------------------------------"
  exit 1
fi

#TODO: Check if db is running

#Clone the local Drupal and Drush to the run directory:
if [ -f ${DCI_REPODIR}/drupal/.git/config ];
  then
    echo "Local Drupal repo found on ${DCI_REPODIR}/drupal/"
  else
    echo ""
    echo "Making onetime Drupal git clone to: ${DCI_REPODIR}/drupal/"
    echo "Press CTRL+c to Cancel"
    sleep 1 #+INFO: https://drupal.org/project/drupal/git-instructions
    cd ${DCI_REPODIR}
    git clone ${DCI_DRUPALREPO} drupal
    cd drupal
    # GET ALL BRANCHES
    actualbranch=$(git branch | awk '{print $2}')
    for remotebranch in $(git branch -a | grep remotes | grep -v ${actualbranch} | grep -v HEAD | grep -v master); do git branch --track ${remotebranch#remotes/origin/} $remotebranch; done
    git remote update
    git pull --all
    echo ""
fi

#install drush via composer on ${DCI_REPODIR}
if [ -f ${DCI_REPODIR}/vendor/drush/drush/drush ];
  then
    echo "Local Drush found on ${DCI_REPODIR}/vendor/drush/drush/drush"
  else
	cd ${DCI_REPODIR}
	curl -sS https://getcomposer.org/installer | php -- --install-dir=${DCI_REPODIR}
	${DCI_REPODIR}/composer.phar -d="${DCI_REPODIR}" global require drush/drush:dev-master
fi

# Update Drupal repo 
if [[ $DCI_UPDATEREPO = "true" ]]
  then
    echo "Updating Drupal git..."
    cd ${DCI_REPODIR}/drupal
    pwd
    git remote update
    git fetch --all
    git pull --all
fi

#Check our git version and make it compatible with < 1.8
gitver=$(git --version | awk '{print $3}')
gitlast=$(echo -e "$gitver\n1.8.0.0" | sort -nr | head -n1)
[ "$gitlast" = "$gitver" ] && SB="--single-branch" || SB=""

#Clone the local repo to the run directory:
git clone ${SB} --branch ${DRUPALBRANCH} ${DCI_REPODIR}/drupal/ ${BUILDSDIR}/${DCI_IDENTIFIER}/

# Make it writable for artifacts
mkdir -p  ${BUILDSDIR}/${DCI_IDENTIFIER}/results
chmod a+w ${BUILDSDIR}/${DCI_IDENTIFIER}/results
chmod a+w ${BUILDSDIR}/${DCI_IDENTIFIER}/

#Change to the branch we would like to test
if [[ ${DRUPALBRANCH} != "" ]]
  then
    cd ${BUILDSDIR}/${DCI_IDENTIFIER}/
    git checkout ${DRUPALBRANCH} 2>&1 | head -n3
    echo ""
fi

if [[ ${DBTYPE} = "sqlite" ]]
  then
    DBLINK=""
fi

#DCI_DEPENDENCIES="module1,module2,module3"
#Get the dependecies
if [[ $DCI_DEPENDENCIES = "" ]]
  then
    echo -e "NOTICE: \$DCI_DEPENDENCIES has no modules declared...\n"
  else
      cd ${BUILDSDIR}/${DCI_IDENTIFIER}/
    for DEP in $(echo "$DCI_DEPENDENCIES" | tr "," "\n")
      do
      echo "Project: $DEP"
      ${DCI_REPODIR}/vendor/drush/drush/drush -y dl ${DEP}
    done
    echo ""
fi

#DCI_DEPENDENCIES_GIT="gitrepo1,branch;gitrepo2,branch"
#Get the git dependecies
if [[ $DCI_DEPENDENCIES_GIT = "" ]]
  then
    echo -e "NOTICE: \$DCI_DEPENDENCIES_GIT has nothing declared...\n"
  else
     ARRAY=($(echo "${DCI_DEPENDENCIES_GIT}" | tr ";" "\n"))
     mkdir -p ${BUILDSDIR}/${DCI_IDENTIFIER}/${MODULESPATH}
     cd ${BUILDSDIR}/${DCI_IDENTIFIER}/${MODULESPATH}
     for row in ${ARRAY[@]}
      do
      read gurl gbranch <<<$(echo "${row}" | tr "," " ");
      echo "Git URL: $gurl Branch: $gbranch "
      git clone --branch $gbranch $gurl
    done
    echo ""
fi

#DCI_DEPENDENCIES_TGZ="module1_url.tgz,module1_url.tgz,module1_url.tgz"
#Get the tgz dependecies
if [[ $DCI_DEPENDENCIES_TGZ = "" ]]
  then
    echo -e "NOTICE: \$DCI_DEPENDENCIES_TGZ has nothing declared...\n"
  else
     ARRAY=($(echo "${DCI_DEPENDENCIES_TGZ}" | tr "," "\n"))
     mkdir -p ${BUILDSDIR}/${DCI_IDENTIFIER}/${MODULESPATH}
     cd ${BUILDSDIR}/${DCI_IDENTIFIER}/${MODULESPATH}
     for row in ${ARRAY[@]}
      do
      echo "TGZ URL: ${row}  "
      curl -s ${row} | tar xzf -
    done
    echo ""
fi

#PATCH="patch_url,apply_dir;patch_url,apply_dir;"
#Apply Patch if any
if [[ $PATCH = "" ]]
  then
    echo -e "NOTICE: \$PATCH variable has no patch to apply...\n"
  else
    ARRAY=($(echo "${PATCH}" | tr ";" "\n"))
    for row in ${ARRAY[@]}
      do
      read purl dir <<<$(echo "${row}" | tr "," " ");
      cd ${BUILDSDIR}/${DCI_IDENTIFIER}/${dir}/
      if $(echo "$purl" | egrep -q "^http");
        then
          curl --retry 3 -s $purl > patch
        else
          cat  $purl > patch
      fi
      echo "Applying Patch: ${purl}"
      file patch
      git apply --verbose --index patch
      if [ "$?" == "0" ]; then
        echo -e "Done!\n"
      else
        echo "Patch var did not apply to the dir."
        echo "Please check if:"
        echo "  - Patch format is correct."
        echo "  - Module has been checked out."
        echo "  - Patch applies against the version of the module."
        echo "  - You provided the correct apply directory."
        exit 1
      fi
    done
fi


echo "------------------------- ENVIRONMENT VARIABLES IN USE -------------------------"
#Write all ENV VARIABLES to ${BUILDSDIR}/${DCI_IDENTIFIER}/test.info
echo "DCI_IDENTIFIER=\"${DCI_IDENTIFIER}\"
DRUPALBRANCH=\"${DRUPALBRANCH}\"
DRUPALVERSION=\"${DRUPALVERSION}\"
DCI_UPDATEREPO=\"${DCI_UPDATEREPO}\"
DCI_REPODIR=\"${DCI_REPODIR}\"
DCI_DRUPALREPO=\"${DCI_DRUPALREPO}\"
DCI_DRUSHREPO=\"${DCI_DRUSHREPO}\"
BUILDSDIR=\"${BUILDSDIR}\"
WORKSPACE=\"${WORKSPACE}\"
DCI_DEPENDENCIES=\"${DCI_DEPENDENCIES}\"
DCI_DEPENDENCIES_GIT=\"${DCI_DEPENDENCIES_GIT}\"
DCI_DEPENDENCIES_TGZ=\"${DCI_DEPENDENCIES_TGZ}\"
MODULESPATH=\"${MODULESPATH}\"
PATCH=\"${PATCH}\"
DBUSER=\"${DBUSER}\"
DBPASS=\"${DBPASS}\"
DBTYPE=\"${DBTYPE}\"
DBVER=\"${DBVER}\"
DBCONTAINER=\"${DBCONTAINER}\"
DBLINK=\"${DBLINK}\"
CMD=\"${CMD}\"
INSTALLER=\"${INSTALLER}\"
VERBOSE=\"${VERBOSE}\"
PHPVERSION=\"${PHPVERSION}\"
CONCURRENCY=\"${CONCURRENCY}\"
RUNSCRIPT=\"${RUNSCRIPT}\"
TESTGROUPS=\"${TESTGROUPS}\"
" | tee ${BUILDSDIR}/${DCI_IDENTIFIER}/test.info

#Let the tests start
echo "------------------------- STARTING DOCKER CONTAINER ----------------------------"
RUNCMD="/usr/bin/time -p docker run ${DBLINK} --name=${DCI_IDENTIFIER} -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${DCI_IDENTIFIER}/:/var/www:rw -p 80 -t drupalci/db-web${PHPVERSION} ${CMD}"
/usr/bin/time -p docker run ${DBLINK} --name=${DCI_IDENTIFIER} -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${DCI_IDENTIFIER}/:/var/www:rw -p 80 -t drupalci/web-${PHPVERSION} ${CMD}

echo "exited $?"

echo "Saving image ${DCI_IDENTIFIER}"
docker commit ${DCI_IDENTIFIER} drupal/${DCI_IDENTIFIER}
# echo "If you need to debug this container run:"
# echo "docker run -d=false -i=true drupal/${DCI_IDENTIFIER} /bin/bash"

echo "--------------------------------------------------------------------------------"
echo "Results directory: ${BUILDSDIR}/${DCI_IDENTIFIER}/results/"
echo "Make sure to clean up old Builds on ${BUILDSDIR} to save disk space"
echo "--------------------------------------------------------------------------------"
echo "Docker run command:"
echo "${RUNCMD}"

exit 0
