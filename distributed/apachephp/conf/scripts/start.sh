#!/bin/bash -xe

# Start apache
echo "Operation [start]..."
a2enmod rewrite
apachectl start

echo "Operation [install]..."
#For now we use Drush to install the site but we are going to
#move to other real browser installer
#GET ALL INFO FROM /var/www/test.info:
source /var/www/test.info

#TODO: OTHER http://drush.ws/#site-install
cd /var/www/
mkdir -p sites/default/files/
chown www-data:www-data sites/default/files/

if [[ $DBTYPE = "sqlite" ]]
  then
    drush si -y --db-url=sqlite://sites/default/files/.ht.sqlite --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
  else
    drush si -y --db-url=mysql://${DBUSER}:${DBPASS}@${DB_PORT_3606_TCP_ADDR}/${IDENTIFIER} --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
fi

drush -y en simpletest

# Run the test suite.
echo "Operation [run tests]..."
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${RUNSCRIPT}"
