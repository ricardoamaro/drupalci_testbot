#!/bin/bash -xe

# Implies there is a "git clone --branch 7.x http://git.drupal.org/project/drupal.git" on /somedir/drupal-7/ (or 8)
if [[ -z "$DRUPALVERSION" ]]
  then
    DRUPALVERSION="7.26" #SHOULD be passed via argument
    DRUPALBRANCH=$(echo $DRUPALVERSION | awk -F. '{print $1}') 
    IDENTIFIER="testid-iteration" #SHOULD be passed via argument
    REPODIR="/opt" #Change to the volume on the host
    DBUSER="drupaltestbot" 
    DBPASS="drupaltestbotpw"
    CONCURRENCY="4" 
    GROUPS="NonDefaultBlockAdmin" #TESTS TO RUN
    RUNSCRIPT="php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/www/results' ${GROUPS} "
fi

git clone ${REPODIR}/drupal-${DRUPALBRANCH}/ ${REPODIR}/${IDENTIFIER}/
cd ${REPODIR}/${IDENTIFIER}/ ; git checkout ${DRUPALVERSION}

#Write all ENV VARIABLES to ${REPODIR}/${IDENTIFIER}/test.info
#For now format it in a source format

echo "
DRUPALVERSION=\"${DRUPALVERSION}\"
DRUPALBRANCH=\"${DRUPALBRANCH}\"
IDENTIFIER=\"${IDENTIFIER}\"
REPODIR=\"${REPODIR}\"
DBUSER=\"${DBUSER}\"
DBPASS=\"${DBPASS}\"
RUNSCRIPT=\"${RUNSCRIPT}\"
" > ${REPODIR}/${IDENTIFIER}/test.info

docker run -d=false -i=true --link=drupaltestbot-db:db -v=/var/log:/var/host_logs:ro -v=${REPODIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web /bin/bash

exit 0




