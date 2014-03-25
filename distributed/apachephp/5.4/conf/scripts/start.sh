#!/bin/bash


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

# Install the site. Enable Simpletest (are we going to use phing?)
echo "Operation [install]..."
cd /root/drupal-install && phing install -Dapp.installUrl='core/install.php?langcode=en&profile=testing'
cd /root/drupal-install && phing enable:simpletest

#Operation [install via drush]...
#drush si command=cd sites/default/files/checkout && drush si -y --db-url=mysql://root@localhost/drupaltestbotmysql --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
#cd sites/default/files/checkout && drush si -y --db-url=mysql://root@localhost/drupaltestbotmysql --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com 2>&1


# Show the environment variables for debugging.
echo "##################################################"
echo ""
echo "These are environment variables we need to know "
echo "for debugging."
echo ""
echo "CONCURRENCY: $CONCURRENCY"
echo "GROUPS: $GROUPS"
echo ""
echo "##################################################"

# Run the test suite.
echo "Operation [run tests]..."
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && php ./core/scripts/run-tests.sh --php `which php` --url 'http://localhost' --color --concurrency $CONCURRENCY --xml '/var/www/results' '$GROUPS'"

echo "Operation [giving you a shell]..."
/bin/bash

