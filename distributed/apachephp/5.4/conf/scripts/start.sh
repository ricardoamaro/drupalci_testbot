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

drush si -y --db-url=mysql://root@localhost/${IDENTIFIER} --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com

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
