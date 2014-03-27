#!/bin/bash -e

# Implies there is a "git clone --branch 7(8).x http://git.drupal.org/project/drupal.git" on /$REPODIR/drupal-7(8)
DRUPALVERSION=${DRUPALVERSION:-""}
DRUPALBRANCH=${DRUPALBRANCH:-"7"}
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
TESTGROUPS=${TESTGROUPS:-"--class NonDefaultBlockAdmin"} #TESTS TO RUN
RUNSCRIPT=${RUNSCRIPT:-"php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "}

mkdir -p ${BUILDSDIR}/${IDENTIFIER}/
mkdir -p ${REPODIR}/${IDENTIFIER}/

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
  echo "Container: testbot-web${PHPVERSION} available"
  echo "Running with PHP${PHPVERSION} drupal/testbot-web${PHPVERSION}"
  else
  echo "ERROR. Image testbot-web${PHPVERSION} needs to be built with: sudo ./build ${PHPVERSION}"
  exit 1
fi

#TODO: Check if db is running

#Clone the local Drupal and Drush to the run directory:
if $(grep branch ${REPODIR}/drupal-${DRUPALBRANCH}/.git/config | grep -q ${DRUPALBRANCH}) ;
  then 
  echo "Local git repo found on ${REPODIR}/drupal-${DRUPALBRANCH}/"
  else
  echo ""
  echo "Making onetime Drupal git clone to: ${REPODIR}/drupal-${DRUPALBRANCH}/"
  echo "Press CTRL+c to Cancel"
  sleep 1 #+INFO: https://drupal.org/project/drupal/git-instructions
  cd ${REPODIR}
  git clone --branch ${DRUPALBRANCH}.x http://git.drupal.org/project/drupal.git drupal-${DRUPALBRANCH}
  echo ""
  echo "Making onetime Drush git clone to: ${REPODIR}/drush/"
  git clone http://git.drupal.org/project/drush.git drush
fi

#Clone the local repo to the run directory:
git clone ${REPODIR}/drupal-${DRUPALBRANCH}/ ${BUILDSDIR}/${IDENTIFIER}/

#Change to the version we would like to test
if [[ $DRUPALVERSION != "" ]]
  then
    cd ${BUILDSDIR}/${IDENTIFIER}/ 
    git checkout ${DRUPALVERSION} 2>&1 | grep "checking out"
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
DRUPALVERSION=\"${DRUPALVERSION}\"
DRUPALBRANCH=\"${DRUPALBRANCH}\"
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
time docker run -d=false -i=true --link=drupaltestbot-db:db -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web${PHPVERSION}

echo "Test finished run using: ${REPODIR}/${IDENTIFIER}/"
echo ""
exit 0
