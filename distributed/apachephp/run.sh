#!/bin/bash -e

# Implies there is a "git clone  http://git.drupal.org/project/drupal.git" on /$REPODIR/drupal
DRUPALBRANCH=${DRUPALBRANCH:-"7.x"}
DRUPALVERSION=${DRUPALVERSION:-""}
UPDATEREPO=${UPDATEREPO:-"false"}
IDENTIFIER=${IDENTIFIER:-"BUILD-$(date +%Y_%m_%d_%H%M%S)"} 
REPODIR=${REPODIR:-"$HOME/testbotdata"} 
BUILDSDIR=${BUILDSDIR:-"$REPODIR"}
WORKSPACE=${WORKSPACE:-"$BUILDSDIR/$IDENTIFIER/"}
DEPENDENCIES=${DEPENDENCIES:-""}
PATCH=${PATCH:-""} #comma separated for several
DBUSER=${DBUSER:-"drupaltestbot"} 
DBPASS=${DBPASS:-"drupaltestbotpw"}
DBTYPE=${DBTYPE:-"mysql"} 
PHPVERSION=${PHPVERSION:-"5.4"}
CONCURRENCY=${CONCURRENCY:-"4"} #How many cpus to use per run
TESTGROUPS=${TESTGROUPS:-"--class NonDefaultBlockAdmin"} #TESTS TO RUN from https://api.drupal.org/api/drupal/classes/8

case $DRUPALBRANCH in
  8.x) RUNNER="./core/scripts/run-tests.sh"
    ;;
  *) RUNNER="./scripts/run-tests.sh"
    ;;
esac
    
RUNSCRIPT=${RUNSCRIPT:-"php ${RUNNER} --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "}

mkdir -p ${BUILDSDIR}/${IDENTIFIER}/
mkdir -p ${REPODIR}

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
  echo "Running with PHP${PHPVERSION} drupal/testbot-web${PHPVERSION}"
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
  if [ ! -f ${REPODIR}/drush/drush ]; 
    then
    echo "Making onetime Drush git clone to: ${REPODIR}/drush/"
    git clone http://git.drupal.org/project/drush.git drush
  fi
fi

if [[ $UPDATEREPO = "true" ]]
  then
    echo "Updating git"
    cd ${REPODIR}/drupal
    git pull
    cd ${REPODIR}/drush
    git pull
    echo ""
fi

#Clone the local repo to the run directory:
if [[ $DRUPALBRANCH != "" ]]
  then
    git clone ${REPODIR}/drupal/ ${BUILDSDIR}/${IDENTIFIER}/
  else
    git clone --branch ${DRUPALBRANCH} ${REPODIR}/drupal/ ${BUILDSDIR}/${IDENTIFIER}/
fi

#Change to the version we would like to test
if [[ $DRUPALVERSION != "" ]]
  then
    cd ${BUILDSDIR}/${IDENTIFIER}/ 
    git checkout ${DRUPALVERSION} 2>&1 | head -n10
    echo ""
fi

#Get the dependecies
if [[ $DEPENDENCIES = "" ]]
  then
    echo -e "WARNING: \$DEPENDENCIES has no modules declared...\n"
  else
    for DEP in $(echo "$DEPENDENCIES" | tr "," "\n")
      do 
      echo "Project: $DEP"
      ${HOME}/testbotdata/drush/drush -y dl ${DEP}
    done  
    echo ""
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
DRUPALBRANCH=\"${DRUPALBRANCH}\"
DRUPALVERSION=\"${DRUPALVERSION}\"
IDENTIFIER=\"${IDENTIFIER}\"
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
echo "---- STARTING DOCKER CONTAINER ----"
time docker run -d=false -i=true --link=drupaltestbot-db:db --name=${IDENTIFIER} -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web${PHPVERSION}

echo "Test finished run using: ${REPODIR}/${IDENTIFIER}/"
echo ""
exit 0
