#!/bin/bash -e

# Implies there is a "git clone --branch 7(8).x http://git.drupal.org/project/drupal.git" on /$REPODIR/drupal-7(8)
DRUPALVERSION=${DRUPALVERSION:-"7.26"} #SHOULD be passed via argument or default to 7.26
DRUPALBRANCH=$(echo $DRUPALVERSION | awk -F. '{print $1}')
IDENTIFIER=${IDENTIFIER:-"BUILD-$(date +%Y_%m_%d_%H%M%S)"} #SHOULD be passed via argument
REPODIR=${REPODIR:-"$HOME/testbotdata"} #Change to the volume on the host
BUILDSDIR=${BUILDSDIR:-"$REPODIR"}
WORKSPACE=${WORKSPACE:-"$BUILDSDIR/$IDENTIFIER/"}
PATCH=${PATCH:-""} #comma separated for several
DBUSER=${DBUSER:-"drupaltestbot"} 
DBPASS=${DBPASS:-"drupaltestbotpw"}
DBTYPE=${DBTYPE:-"mysql"} 
PHPVERSION=${PHPVERSION:-"5.4"}
CONCURRENCY=${CONCURRENCY:-"4"} #How many cpus to use per run
TESTGROUPS=${TESTGROUPS:-"--class NonDefaultBlockAdmin"} #TESTS TO RUN
RUNSCRIPT=${RUNSCRIPT:-"php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "}

#TODO: Check if db is running

#TODO: Check for web with PHPVERSION

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
  RESULT=$(mkdir -p ${REPODIR})
  cd ${REPODIR}
  git clone --branch ${DRUPALBRANCH}.x http://git.drupal.org/project/drupal.git drupal-${DRUPALBRANCH}
fi

# Apply Patch if any
if [[ -z $PATCH ]]
  then
    echo "Applying Patch"
    # Apply Patch
  else
    echo "\$PATCH variable has no patch to apply"
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
PATCH=\"${PATCH}\"
DBUSER=\"${DBUSER}\"
DBPASS=\"${DBPASS}\"
PHPVERSION=\"${PHPVERSION}\"
CONCURRENCY=\"${CONCURRENCY}\" 
TESTGROUPS=\"${TESTGROUPS}\"
RUNSCRIPT=\"${RUNSCRIPT}\"
" > ${REPODIR}/${IDENTIFIER}/test.info

docker run -d=false -i=true --link=drupaltestbot-db:db -v=${WORKSPACE}:/var/workspace:rw -v=${BUILDSDIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web

echo "Test finished run using: ${REPODIR}/${IDENTIFIER}/"

exit 0




