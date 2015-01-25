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
#               IRC: #drupal-testing
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
DCI_PATCH:         Local or remote Patches to be applied.
               Format: patch_location,apply_dir;patch_location,apply_dir;...
DCI_DEPENDENCIES:  Contrib projects to be downloaded & patched.
               Format: module1,module2,module2...
DCI_DEPENDENCIES_GIT  Format: gitrepo1,branch;gitrepo2,branch;...
DCI_DEPENDENCIES_TGZ  Format: module1_url.tgz,module1_url.tgz,...
DCI_DRUPALBRANCH:  Default is '8.0.x'
DCI_DRUPALVERSION: Default is '8'
DCI_TESTGROUPS:    Tests to run. Default is '--class NonDefaultBlockAdmin'
               A list is available at the root of this project.
DCI_VERBOSE:       Default is 'false'
DCI_DBTYPE:        Default is 'mysql-5.5' from mysql/sqlite/pgsql
DCI_DBVER:         Default is '5.5'.  Used to override the default version for a given database type.
DCI_ENTRYPOINT:    Default is none. Executes other funcionality in the container prepending CMD.
DCI_CMD:           Default is none. Normally use '/bin/bash' to debug the container
DCI_INSTALLER:     Default is none. Try to use core non install tests.
DCI_UPDATEREPO:    Force git pull of Drupal & Drush. Default is 'false'
DCI_IDENTIFIER:    Automated Build Identifier. Only [a-z0-9-_.] are allowed
DCI_REPODIR:       Default is 'HOME/testbotdata'
DCI_DRUPALREPO:    Default is 'http://git.drupal.org/project/drupal.git'
DCI_DRUSHREPO:     Default is 'https://github.com/drush-ops/drush.git'
DCI_BUILDSDIR:     Default is  equal to DCI_REPODIR
DCI_WORKSPACE:     Default is 'HOME/testbotdata/DCI_IDENTIFIER/'
DCI_DBUSER:        Default is 'drupaltestbot'
DCI_DBPASS:        Default is 'drupaltestbotpw'
DCI_DBCONTAINER:   Default is 'drupaltestbot-db-mysql-5.5'
DCI_PHPVERSION:    Default is '5.4'
DCI_CONCURRENCY:   Default is '4'  #How many cpus to use per run
DCI_RUNSCRIPT:     Default is 'php RUNNER --php /usr/bin/php --url 'http://localhost' --color --concurrency  DCI_CONCURRENCY  --verbose --xml '/var/workspace/results'  DCI_TESTGROUPS  | tee /var/www/test.stdout ' "
echo -e "\n\nExamples:\t\e[38;5;148msudo {VARIABLES} ./run.sh\e[39m "
echo -e "
Run Action and Node tests, 2 LOCAL patches, using 4 CPUs, against D8:
.....................................................................
sudo DCI_TESTGROUPS=\"Action,Node\" CURRENCY=\"4\" DCI_DRUPALBRANCH=\"8.0.x\"  DCI_PATCH=\"/tmp/1942178-config-schema-user-28.patch,.;/tmp/1942178-config-schema-30.patch,.\" ./run.sh

Run all tests using 4 CPUs, 1 core patch against D8:
.....................................................................
sudo DCI_TESTGROUPS=\"--all\" DCI_CONCURRENCY=\"4\" DCI_DRUPALBRANCH=\"8.0.x\" DCI_PATCH=\"https://drupal.org/files/issues/1942178-config-schema-user-28.patch,.\" ./run.sh
"
  exit 0
fi

parse_yaml() {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\)\($w\)$s:$s\"\(.*\)\"$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}

# Source $HOME/.drupalci/config environment variables:
if [ -f $HOME/.drupalci/config.yml ];
  then
    echo "Sourcing your default variables from $HOME/.drupalci/config.yml ";
    eval $(parse_yaml $HOME/.drupalci/config.yml);
  elif [ -f $HOME/.drupalci/config ];
  then
    echo "Sourcing your default variables from $HOME/.drupalci/config ";
    source $HOME/.drupalci/config;
fi

# A list of variables that we only set if empty. Export them before running the script.
# Note: Any variable already set on a higher level will keep it's value.

DCI_IDENTIFIER=${DCI_IDENTIFIER:-"build_$(date +%Y_%m_%d_%H%M%S)"}
DCI_DRUPALBRANCH=${DCI_DRUPALBRANCH:-"8.0.x"}
DCI_DRUPALVERSION=${DCI_DRUPALVERSION:-"$(echo $DCI_DRUPALBRANCH | awk -F. '{print $1}')"}
DCI_UPDATEREPO=${DCI_UPDATEREPO:-"false"}
DCI_REPODIR=${DCI_REPODIR:-"$HOME/testbotdata"}
DCI_DRUPALREPO=${DCI_DRUPALREPO:-"http://git.drupal.org/project/drupal.git"}
DCI_DRUSHREPO=${DCI_DRUSHREPO:-"https://github.com/drush-ops/drush.git"}
DCI_BUILDSDIR=${DCI_BUILDSDIR:-"$DCI_REPODIR"}
DCI_WORKSPACE=${DCI_WORKSPACE:-"$DCI_BUILDSDIR/$DCI_IDENTIFIER/"}
DCI_DEPENDENCIES=${DCI_DEPENDENCIES:-""}
DCI_DEPENDENCIES_GIT=${DCI_DEPENDENCIES_GIT:-""}
DCI_DEPENDENCIES_TGZ=${DCI_DEPENDENCIES_TGZ:-""}  #TODO
DCI_PATCH=${DCI_PATCH:-""}
DCI_DBUSER=${DCI_DBUSER:-"drupaltestbot"}
DCI_DBPASS=${DCI_DBPASS:-"drupaltestbotpw"}
DCI_DBTYPE=${DCI_DBTYPE:-"mysql"} #mysql/pgsql/sqlite
DCI_DBVER=${DCI_DBVER:-"5.5"}
DCI_ENTRYPOINT=${DCI_ENTRYPOINT:-""}
[ ! -z "$DCI_ENTRYPOINT" ] && DCI_ENTRYPOINT="--entrypoint $DCI_ENTRYPOINT"
DCI_CMD=${DCI_CMD:-""}
[ ! -z "$DCI_CMD" ] && DCI_INTERACTIVE="-i"
DCI_INSTALLER=${DCI_INSTALLER:-"none"}
DCI_VERBOSE=${DCI_VERBOSE:-"false"}
DCI_PHPVERSION=${DCI_PHPVERSION:-"5.4"}
DCI_CONCURRENCY=${DCI_CONCURRENCY:-"4"} #How many cpus to use per run
DCI_TESTGROUPS=${DCI_TESTGROUPS:-"Bootstrap"} #TESTS TO RUN from https://api.drupal.org/api/drupal/classes/8

# run-tests.sh place changes on 8.0.x
case $DCI_DRUPALVERSION in
  8) RUNNER="./core/scripts/run-tests.sh"
     DCI_MODULESPATH="./modules"
    ;;
  *) RUNNER="./scripts/run-tests.sh"
     DCI_MODULESPATH="./sites/all/modules"
    ;;
esac

case $DCI_DBTYPE in
  mongodb)
    DBPORT="27017"
    DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-mongodb-2.6"}
  ;;
  pgsql)
     if [ -z ${DCI_DBVER+x} ];
       then
         DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-pgsql-9.1"}
       else
         case $DCI_DBVER in
           8.3)  DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-pgsql-8.3"}
           ;;
           9.1)  DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-pgsql-9.1"}
           ;;
         esac
     fi
     DBPORT="5432"
  ;;
  mariadb)
    if [ -z ${DCI_DBVER+x} ];
      then
        DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-mariadb-5.5"}
      else
        case $DCI_DBVER in
          5.5)  DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-mariadb-5.5"}
          ;;
          10.0)   DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-mariadb-10.0"}
          ;;
        esac
    fi
    DBPORT="3306"
  ;;
  mysql | *)
    DBPORT="3306"
    DCI_DBCONTAINER=${DCI_DBCONTAINER:-"drupaltestbot-db-mysql-5.5"}
  ;;
esac
DCI_DBLINK=${DCI_DBLINK:-"--link=${DCI_DBCONTAINER}:db"}

case $DCI_VERBOSE in
  true) VERBO="--verbose"
    ;;
  *) VERBO=""
    ;;
esac

DCI_RUNSCRIPT=${DCI_RUNSCRIPT:-"php ${RUNNER} --php /usr/bin/php --url 'http://localhost' --color --concurrency ${DCI_CONCURRENCY} ${VERBO} --xml '/var/workspace/results'"}

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

mkdir -p ${DCI_REPODIR}

# Check if we have free disk space
FREEDISK=$(df -m ${DCI_BUILDSDIR} | tail -n1 | awk '{print $4}')
if (( $FREEDISK <= 100 ));
  then
    echo ""
    echo "ERROR! Low disk space!";
    echo ""
    df -hT ${DCI_BUILDSDIR}
    echo ""
    echo "Try to clean up some disk space from ${DCI_BUILDSDIR}"
    echo "A minimum of 100MB is required..."
    exit 1;
fi

# If we are using a non-sqlite database, make sure the container is there
if [[ $DCI_DBTYPE != "sqlite" ]]
  then
    set +e
    RUNNING=$(docker ps | grep ${DCI_DBCONTAINER} | grep -s ${DBPORT})
    set -e
    if [[ $RUNNING = "" ]]
      then
        echo "------------------------------------------------------------------------------"
        echo -e "ERROR: There is no ${DCI_DBCONTAINER} container running..."
        echo -e "Please make sure you built the image and started it:"
        echo -e "sudo ./scripts/build_all.sh refresh \n"
        echo -e "Also please make sure port ${DBPORT} is not being used \nand ${DCI_DBTYPE} is stopped on the host."
        echo "------------------------------------------------------------------------------"
        exit 1
    fi
fi

#Ensure DCI_PHPVERSION is set
case $DCI_PHPVERSION in
  5.3)
    DCI_PHPVERSION="5.3"
    ;;
  5.5)
    DCI_PHPVERSION="5.5"
    ;;
  5.6)
    DCI_PHPVERSION="5.6"
    ;;
  *)
    DCI_PHPVERSION="5.4"
    ;;
esac

#Check if the web container is built
if $(docker images | grep -q web-${DCI_PHPVERSION});
  then
  echo "------------------------------------------------------------------------------"
  echo "Containers: web-${DCI_PHPVERSION} and ${DCI_DBCONTAINER} available"
  echo "Running PHP${DCI_PHPVERSION}/${DCI_DBTYPE} on drupalci/web-${DCI_PHPVERSION} at $(date -u)"
  echo "------------------------------------------------------------------------------"
  else
  echo "------------------------------------------------------------------------------"
  echo "ERROR. Image drupalci/web-${DCI_PHPVERSION} needs to be built with:"
  echo "cd containers/web/web-${DCI_PHPVERSION}"
  echo "sudo ./build.sh"
  echo "------------------------------------------------------------------------------"
  exit 1
fi

#TODO: Check if db is running

#Clone the local Drupal and Drush to the run directory:
if [ -f ${DCI_REPODIR}/drupal/.git/config ];
  then
    echo "Local Drupal repo found on ${DCI_REPODIR}/drupal/"
  else
    echo ""
    echo "Making one-time Drupal git clone to: ${DCI_REPODIR}/drupal/"
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
git clone ${SB} --branch ${DCI_DRUPALBRANCH} ${DCI_REPODIR}/drupal/ ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/

# Make it writable for artifacts
mkdir -p  ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/results
chmod a+w ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/results
chmod a+w ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/

#Change to the branch we would like to test
if [[ ${DCI_DRUPALBRANCH} != "" ]]
  then
    cd ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/
    git checkout ${DCI_DRUPALBRANCH} 2>&1 | head -n3
    echo ""
fi

if [[ ${DCI_DBTYPE} = "sqlite" ]]
  then
    DCI_DBLINK=""
fi

if [[ $DCI_DBTYPE = "mongodb" ]]
  then
    mkdir -p ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/drivers/lib/Drupal/Driver/Database/
    ln -s ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${DCI_MODULESPATH}/mongodb/drivers/mongodb ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/drivers/lib/Drupal/Driver/Database/mongodb
    DCI_DEPENDENCIES_GIT=$DCI_DEPENDENCIES${DCI_DEPENDENCIES+;}http://git.drupal.org/project/mongodb.git,8.x-1.x
fi

#DCI_DEPENDENCIES="module1,module2,module3"
#Get the dependecies
if [[ $DCI_DEPENDENCIES = "" ]]
  then
    echo -e "NOTICE: \$DCI_DEPENDENCIES has no modules declared...\n"
  else
      cd ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/
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
     mkdir -p ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${DCI_MODULESPATH}
     cd ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${DCI_MODULESPATH}
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
     mkdir -p ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${DCI_MODULESPATH}
     cd ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${DCI_MODULESPATH}
     for row in ${ARRAY[@]}
      do
      echo "TGZ URL: ${row}  "
      curl -s ${row} | tar xzf -
    done
    echo ""
fi

#DCI_PATCH="patch_url,apply_dir;patch_url,apply_dir;"
#Apply Patch if any
if [[ $DCI_PATCH = "" ]]
  then
    echo -e "NOTICE: \$DCI_PATCH variable has no patch to apply...\n"
  else
    ARRAY=($(echo "${DCI_PATCH}" | tr ";" "\n"))
    for row in ${ARRAY[@]}
      do
      read purl dir <<<$(echo "${row}" | tr "," " ");
      cd ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/${dir}/
pwd
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


echo "------------------------ ENVIRONMENT VARIABLES IN USE ------------------------"
#Write all ENV VARIABLES to ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/test.info
echo "DCI_IDENTIFIER=\"${DCI_IDENTIFIER}\"
DCI_DRUPALBRANCH=\"${DCI_DRUPALBRANCH}\"
DCI_DRUPALVERSION=\"${DCI_DRUPALVERSION}\"
DCI_TESTGROUPS=\"${DCI_TESTGROUPS}\"
DCI_PATCH=\"${DCI_PATCH}\"
DCI_PHPVERSION=\"${DCI_PHPVERSION}\"
DCI_DBTYPE=\"${DCI_DBTYPE}\"
DCI_DBVER=\"${DCI_DBVER}\"
DCI_CONCURRENCY=\"${DCI_CONCURRENCY}\"
DCI_INSTALLER=\"${DCI_INSTALLER}\"
DCI_VERBOSE=\"${DCI_VERBOSE}\"
DCI_REPODIR=\"${DCI_REPODIR}\"
DCI_UPDATEREPO=\"${DCI_UPDATEREPO}\"
DCI_DRUPALREPO=\"${DCI_DRUPALREPO}\"
DCI_DRUSHREPO=\"${DCI_DRUSHREPO}\"
DCI_BUILDSDIR=\"${DCI_BUILDSDIR}\"
DCI_WORKSPACE=\"${DCI_WORKSPACE}\"
DCI_DEPENDENCIES=\"${DCI_DEPENDENCIES}\"
DCI_DEPENDENCIES_GIT=\"${DCI_DEPENDENCIES_GIT}\"
DCI_DEPENDENCIES_TGZ=\"${DCI_DEPENDENCIES_TGZ}\"
DCI_MODULESPATH=\"${DCI_MODULESPATH}\"
DCI_DBUSER=\"${DCI_DBUSER}\"
DCI_DBPASS=\"${DCI_DBPASS}\"
DCI_DBCONTAINER=\"${DCI_DBCONTAINER}\"
DCI_DBLINK=\"${DCI_DBLINK}\"
DCI_CMD=\"${DCI_CMD}\"
DCI_INTERACTIVE=\"${DCI_INTERACTIVE}\"
DCI_RUNSCRIPT=\"${DCI_RUNSCRIPT}\"
VERBO=\"${VERBO}\"
" | tee ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/test.info

#Let the tests start
echo "------------------------ STARTING DOCKER CONTAINER ---------------------------"
[ ! -z "$DCI_CMD" ] && echo "-------- Interactive mode activated! Use: /start.sh to run tests -------------"
DCI_RUNCMD="/usr/bin/time -p docker run ${DCI_DBLINK} --name=${DCI_IDENTIFIER} -v=${DCI_WORKSPACE}:/var/workspace:rw -v=${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/:/var/www:rw -p 80 ${DCI_INTERACTIVE} -t drupalci/web-${DCI_PHPVERSION} ${DCI_CMD}"
/usr/bin/time -p docker run ${DCI_DBLINK} --name=${DCI_IDENTIFIER} -v=${DCI_WORKSPACE}:/var/workspace:rw -v=${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/:/var/www:rw -p 80 ${DCI_INTERACTIVE} -t ${DCI_ENTRYPOINT} drupalci/web-${DCI_PHPVERSION} ${DCI_CMD} 

echo 
echo "Saving image ${DCI_IDENTIFIER} at $(date -u):"
docker commit ${DCI_IDENTIFIER} drupal/${DCI_IDENTIFIER}
# echo "If you need to debug this container run:"
# echo "docker run -d=false -i=true drupal/${DCI_IDENTIFIER} /bin/bash"

echo "------------------------------------------------------------------------------"
echo "Results directory: ${DCI_BUILDSDIR}/${DCI_IDENTIFIER}/results/"
echo "Clean up old Builds on ${DCI_BUILDSDIR} to save disk space"
echo "------------------------------------------------------------------------------"
echo "Docker run command:"
echo "${DCI_RUNCMD}"
