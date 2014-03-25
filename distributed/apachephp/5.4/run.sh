sudo docker run -d=false -t drupal/testbot-web


exit 0

################

#CONTAINER=$( 
sudo docker run -d=false -p=80:80 -p=9000:22 -t -i -v /var/logs:/var/host_logs:rw v /tmp/www:/var/www:rw drupal/testbot-web 

echo "Container: ${CONTAINER} started"
sleep 30
sudo docker logs ${CONTAINER}




docker run -d=false -name=drupaltestbot-web -p=80:80 -p=9000:22 -link=drupaltestbot-db:db -volumes-from=my-docroot drupaltestbot-apachephp /bin/bash


docker rmi drupal/testbot-web

