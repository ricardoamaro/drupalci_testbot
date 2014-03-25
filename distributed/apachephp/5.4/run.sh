#!/bin/bash -xe

# Implies there is a "git clone --branch 7.x http://git.drupal.org/project/drupal.git" on /somedir/drupal-7/ (or 8)
if [[ -z "$DRUPALVERSION" ]]
  then
    DRUPALVERSION="7.26" #SHOULD be passed via argument
    DRUPALBRANCH=$(echo $DRUPALVERSION | awk -F. '{print $1}') 
    IDENTIFIER="testid-iteration" #SHOULD be passed via argument
    REPODIR="/opt" #Change to the volume on the host
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
" > ${REPODIR}/${IDENTIFIER}/test.info

docker run -d=false -i=false -t=false -link=drupaltestbot-db:db -v=/var/log:/var/host_logs:ro -v=/${REPODIR}/${IDENTIFIER}/:/var/www:rw -t drupal/testbot-web

exit 0

#########TODELETE:#######

sudo docker run -d=false -p=80:80 -p=9000:22 -t -i -v /var/logs:/var/host_logs:ro -v /tmp/www:/var/www:ro drupal/testbot-web 

echo "Container: ${CONTAINER} started"
sleep 30
sudo docker logs ${CONTAINER}
docker run -d=false -name=drupaltestbot-web -p=80:80 -p=9000:22 -link=drupaltestbot-db:db -volumes-from=my-docroot drupaltestbot-apachephp /bin/bash
docker rmi drupal/testbot-web

