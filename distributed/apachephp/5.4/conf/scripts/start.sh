#!/bin/bash -xe

# Start apache
echo "Operation [start]..."
a2enmod rewrite
apachectl start

# Setup Drupal mount binded from outside
# TODO ON RUN

# Directories and permissions.
rm -fR /var/www/sites/default/files
rm -fR /var/www/sites/default/private
mkdir /var/www/sites/default/files
mkdir /var/www/sites/default/private
chmod -R 777 /var/www/sites/default/files
chmod -R 777 /var/www/sites/default/private

echo "Operation [install]..."
#For now we use Drush to install the site but we are going to
#move to other real browser installer
#GET ALL INFO FROM /var/www/test.info:
source /var/www/test.info

cd /var/www/
drush si -y --db-url=mysql://${DBUSER}:${DBPASS}@${DB_PORT_3606_TCP_ADDR}/${IDENTIFIER} --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com 
drush -y en simpletest


# Run the test suite.
echo "Operation [run tests]..."
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${RUNSCRIPT}"
