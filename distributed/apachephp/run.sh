#!/bin/bash -e

# Implies there is a "git clone --branch 7(8).x http://git.drupal.org/project/drupal.git" on /$REPODIR/drupal-7(8)
DRUPALVERSION=${DRUPALVERSION:-"7.26"} #SHOULD be passed via argument or default to 7.26
DRUPALBRANCH=$(echo $DRUPALVERSION | awk -F. '{print $1}')
IDENTIFIER=${IDENTIFIER:-"BUILD-$(date +%Y_%m_%d_%H%M%S)"} #SHOULD be passed via argument
REPODIR=${REPODIR:-"$HOME/testbotdata"} #Change to the volume on the host
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

#TODO: DEAL with iteration

#Clone the local repo to the run directory:
if $(grep branch ${REPODIR}/drupal-${DRUPALBRANCH}/.git/config | grep -q ${DRUPALBRANCH}) ;
  then 
  echo "Local git repo found on ${REPODIR}/drupal-${DRUPALBRANCH}/"
  else
  echo ""
  echo "Making onetime Drupal git clone to: ${REPODIR}/drupal-${DRUPALBRANCH}/"
  echo "Press CTRL+c to Cancel"
  sleep 10 #+INFO: https://drupal.org/project/drupal/git-instructions
  cd ${REPODIR}
  git clone --branch ${DRUPALBRANCH}.x http://git.drupal.org/project/drupal.git drupal-${DRUPALBRANCH}
fi

#Clone the local repo to the run directory:
git clone ${REPODIR}/drupal-${DRUPALBRANCH}/ ${REPODIR}/${IDENTIFIER}/
cd ${REPODIR}/${IDENTIFIER}/ ; git checkout ${DRUPALVERSION}

#TODO: GET the dependecies
#git clone each module

if [[ $DEPENDENCIES = "" ]]
  then
    echo "WARNING: \$DEPENDENCIES has no modules declared..."
  else
    #TODO: GET the dependecies
    #git clone each module
    echo ""
fi

#PATCH=${PATCH:-"patch_url,apply_dir;patch_url,apply_dir;"} 

#Apply Patch if any
if [[ $PATCH = "" ]]
  then
    echo "WARNING: \$PATCH variable has no patch to apply..."
  else
    echo "Applying Patch"
    # Apply Patch
    echo "Patch var did not apply to the dir."
    echo "Please check if:
    - Patch format is correct.
    - Module has been checked out.
    - Patch applies against the version of the module.
    - You provided the correct apply directory."
    exit 1
fi

#Write all ENV VARIABLES to ${BUILDSDIR}/${IDENTIFIER}/test.info
#For now it's in a source format
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
" > ${BUILDSDIR}/${IDENTIFIER}/test.info

#Let the tests start
time docker run -d=false -i=true --link=drupaltestbot-db:db -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web${PHPVERSION} 

echo "Test finished run using: ${REPODIR}/${IDENTIFIER}/"

exit 0
