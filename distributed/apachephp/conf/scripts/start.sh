#!/bin/bash -e

#GET ALL INFO FROM /var/www/test.info:
source /var/www/test.info

export PATH=$HOME/bin:$PATH
export DRUSH="/.composer/vendor/drush/drush/drush"

# Start apache
echo "Operation [start]..."
SERVICE='apache2'

if ps ax | grep -v grep | grep $SERVICE > /dev/null
then
  echo "$SERVICE service already running"
else
  echo "Starting $SERVICE service"
  apachectl start 2>/dev/null
fi

echo "Operation [install]..."
#For now we use Drush to install the site but we are going to
#move to other real browser installer

cd /var/www/
echo ""

# --dburl is the effective database connection that is being used for all tests.  Can be any database driver supported by core
# --sqlite database is used for the test runner only (and only contains the simpletest module database schema)
#Example: php ./core/scripts/run-tests.sh --sqlite /tmpfs/drupal/test.sqlite --dburl mysql://username:password@localhost/database --url http://example.com/ --all

if (( $DRUPALVERSION >= 8 ))
  then
  echo "DRUPALVERSION is $DRUPALVERSION"
  echo "Skipping install"
  EXTRA="--sqlite /var/www/test.sqlite --dburl mysql://${DBUSER}:${DBPASS}@${DB_PORT_3306_TCP_ADDR}/${IDENTIFIER} --keep-results"
  #Create drupal database manually
  /usr/bin/mysql -u${DBUSER} -p${DBPASS} -h${DB_PORT_3306_TCP_ADDR} -e "CREATE DATABASE IF NOT EXISTS ${IDENTIFIER} ;"
  else
    if [[ $DBTYPE = "sqlite" ]]
      then
        ${DRUSH} si -y --db-url=sqlite://sites/default/files/.ht.sqlite --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
      else
        ${DRUSH} si -y --db-url=mysql://${DBUSER}:${DBPASS}@${DB_PORT_3306_TCP_ADDR}/${IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
    fi
  ${DRUSH} -y en simpletest
  EXTRA=""
fi
# We are going to write into files make sure it exist:
mkdir -p /var/www/sites/default/files/
chown -fR www-data /var/www/sites/default/files/

# Run the test suite.
echo ""
echo "Operation [run tests]..."
echo "export TERM=linux && cd /var/www && ${RUNSCRIPT} ${EXTRA} ${TESTGROUPS} | tee /var/www/test.results"
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${RUNSCRIPT} ${EXTRA} ${TESTGROUPS} | tee /var/www/test.results" 

#No ugly xml please:
#for i in $(ls results/* ); do tidy -xml -m -i -q "$i"; done
