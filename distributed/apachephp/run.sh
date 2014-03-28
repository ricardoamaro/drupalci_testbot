#!/bin/bash -e

# Implies there is a "git clone  http://git.drupal.org/project/drupal.git" on /$REPODIR/drupal
IDENTIFIER=${IDENTIFIER:-"BUILD_$(date +%Y_%m_%d_%H%M%S)"} 
DRUPALBRANCH=${DRUPALBRANCH:-"7.26"}
DRUPALVERSION=${DRUPALVERSION:-"$(echo $DRUPALBRANCH | awk -F. '{print $1}')"}
UPDATEREPO=${UPDATEREPO:-"false"}
REPODIR=${REPODIR:-"$HOME/testbotdata"} 
DRUSHCOMMIT=${DRUSHCOMMIT:-"master"}
BUILDSDIR=${BUILDSDIR:-"$REPODIR"}
WORKSPACE=${WORKSPACE:-"$BUILDSDIR/$IDENTIFIER/"}
DEPENDENCIES=${DEPENDENCIES:-""}
PATCH=${PATCH:-""} #comma separated for several
DBUSER=${DBUSER:-"drupaltestbot"} 
DBPASS=${DBPASS:-"drupaltestbotpw"}
DBTYPE=${DBTYPE:-"mysql"} #mysql/sqlite
DBLINK=${DBLINK:-"--link=drupaltestbot-db:db"}
CMD=${CMD:-""}
PHPVERSION=${PHPVERSION:-"5.4"}
CONCURRENCY=${CONCURRENCY:-"4"} #How many cpus to use per run
TESTGROUPS=${TESTGROUPS:-"--class NonDefaultBlockAdmin"} #TESTS TO RUN from https://api.drupal.org/api/drupal/classes/8

# run-tests.s place changes on 8.x 
case $DRUPALVERSION in
  8) RUNNER="./core/scripts/run-tests.sh"
    ;;
  *) RUNNER="./scripts/run-tests.sh"
    ;;
esac
    
RUNSCRIPT=${RUNSCRIPT:-"php ${RUNNER} --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "}

mkdir -p ${BUILDSDIR}/${IDENTIFIER}/
mkdir -p ${REPODIR}


# If we are using mysql make sure the conatiner is there
if [[ $DBTYPE = "mysql" ]]
  then
    set +e
    RUNNING=$(sudo docker ps | grep drupaltestbot-db | grep -s 3306)
    set -e
    if [[ $RUNNING = "" ]]
      then
        echo "------------------------------------------------------"
        echo -e "ERROR: There is no Mysql container running..."
        echo -e "Please make sure you built the image and started it."
        echo -e "cd distributed/database/mysql \nsudo ./build.sh \nsudo ./run-server.sh \n"
        echo -e "Also please make sure port 3606 is not being used \nand mysql is stopped on the host."
        echo "------------------------------------------------------"
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
if $(docker images | grep -q testbot-web${PHPVERSION});
  then
  echo "------------------------------------------------------"
  echo "Container: testbot-web${PHPVERSION} available"
  echo "Running PHP${PHPVERSION}/${DBTYPE} on drupal/testbot-web${PHPVERSION}"
  echo "------------------------------------------------------"
  else
  echo "------------------------------------------------------"
  echo "ERROR. Image testbot-web${PHPVERSION} needs to be built with:" 
  echo "sudo ./build ${PHPVERSION}"
  echo "------------------------------------------------------"
  exit 1
fi

#TODO: Check if db is running

#Clone the local Drupal and Drush to the run directory:
if [ -f ${REPODIR}/drupal/.git/config ];
  then 
    echo "Local git repo found on ${REPODIR}/drupal/"
  else
    echo ""
    echo "Making onetime Drupal git clone to: ${REPODIR}/drupal/"
    echo "Press CTRL+c to Cancel"
    sleep 1 #+INFO: https://drupal.org/project/drupal/git-instructions
    cd ${REPODIR}
    git clone http://git.drupal.org/project/drupal.git drupal
    echo ""
fi

if [[ $UPDATEREPO = "true" ]]
  then
    echo "Updating git..."
    cd ${REPODIR}/drupal
    pwd
    git fetch --all
    git pull origin HEAD
    echo ""
fi

#Clone the local repo to the run directory:
git clone --single-branch --branch ${DRUPALBRANCH} ${REPODIR}/drupal/ ${BUILDSDIR}/${IDENTIFIER}/

#Change to the branch we would like to test
if [[ ${DRUPALBRANCH} != "" ]]
  then
    cd ${BUILDSDIR}/${IDENTIFIER}/ 
    git checkout ${DRUPALBRANCH} 2>&1 | head -n3
    echo ""
fi

if [[ ${DBTYPE} = "sqlite" ]]
  then
    DBLINK=""
fi

#PATCH=${PATCH:-"patch_url,apply_dir;patch_url,apply_dir;"} 

#Apply Patch if any
if [[ $PATCH = "" ]]
  then 
    echo -e "WARNING: \$PATCH variable has no patch to apply...\n"
  else
    ARRAY=($(echo "${PATCH}" | tr ";" "\n"))
    for row in ${ARRAY[@]}
      do
      read purl dir <<<$(echo "${row}" | tr "," " ");
      cd ${BUILDSDIR}/${IDENTIFIER}/${dir}/
      if $(echo "$purl" | egrep -q "^http") ;  
        then 
          curl -s $purl > patch
        else 
          cat  $purl > patch
      fi
      echo "Applying Patch: ${purl}"
      git apply --index patch
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

#Write all ENV VARIABLES to ${BUILDSDIR}/${IDENTIFIER}/test.info
echo "
IDENTIFIER=\"${IDENTIFIER}\"
DRUPALBRANCH=\"${DRUPALBRANCH}\"
DRUPALVERSION=\"${DRUPALVERSION}\"
UPDATEREPO=${UPDATEREPO:-"false"}
REPODIR=\"${REPODIR}\"
BUILDSDIR=\"${BUILDSDIR}\"
WORKSPACE=\"${WORKSPACE}\"
DEPENDENCIES=\"${DEPENDENCIES}\"
PATCH=\"${PATCH}\"
DBUSER=\"${DBUSER}\"
DBPASS=\"${DBPASS}\"
DBTYPE=\"${DBTYPE}\" 
PHPVERSION=\"${PHPVERSION}\"
CONCURRENCY=\"${CONCURRENCY}\" 
TESTGROUPS=\"${TESTGROUPS}\"
RUNSCRIPT=\"${RUNSCRIPT}\"
" | tee ${BUILDSDIR}/${IDENTIFIER}/test.info

#Let the tests start
echo "-------------- STARTING DOCKER CONTAINER -------------"
time docker run -d=false -i=true ${DBLINK} --name=${IDENTIFIER} -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web${PHPVERSION} ${CMD}

echo "------------------------------------------------------"
echo "Tests finished using: ${BUILDSDIR}/${IDENTIFIER}/"
echo "Make sure to clean up old Builds on ${BUILDSDIR}"
echo "------------------------------------------------------"

exit 0
