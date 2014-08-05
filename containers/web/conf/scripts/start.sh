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

cd /var/www/
echo ""

# --dburl is the effective database connection that is being used for all tests.  Can be any database driver supported by core
# --sqlite database is used for the test runner only (and only contains the simpletest module database schema)
#Example: php ./core/scripts/run-tests.sh --sqlite /tmpfs/drupal/test.sqlite --dburl mysql://username:password@localhost/database --url http://example.com/ --all

if (( $DRUPALVERSION >= 8 )) && [[ $DCI_INSTALLER = "none" ]];
  then
    echo "DRUPALVERSION is $DRUPALVERSION"
    echo "Skipping operation [install], using core tester instead..."
    #Create drupal database manually because Drupal>=8
    case $DBTYPE in
      pgsql) 
        export PGPASSWORD="${DBPASS}"; 
        export PGUSER="${DBUSER}"; 
        /usr/bin/psql -h ${DB_PORT_5432_TCP_ADDR} -w -c "DROP DATABASE IF EXISTS ${DCI_IDENTIFIER};" 
        /usr/bin/psql -h ${DB_PORT_5432_TCP_ADDR} -w -c "CREATE DATABASE ${DCI_IDENTIFIER} OWNER ${DBUSER} TEMPLATE DEFAULT ENCODING='utf8';"
        DBADDR=${DB_PORT_5432_TCP_ADDR}
      ;;
      mysql|mariadb) 
        DBTYPE="mysql"
        /usr/bin/mysql -u${DBUSER} -p${DBPASS} -h${DB_PORT_3306_TCP_ADDR} -e "CREATE DATABASE IF NOT EXISTS ${DCI_IDENTIFIER} ;"
        DBADDR=${DB_PORT_3306_TCP_ADDR}
      ;;
    esac
    EXTRA="--sqlite /var/www/test.sqlite --dburl ${DBTYPE}://${DBUSER}:${DBPASS}@${DBADDR}/${DCI_IDENTIFIER} --keep-results"
  else
    echo "Operation $DRUPALVERSION [install] using drush... "
    case $DBTYPE in
      sqlite)
        ${DRUSH} si -v -y --db-url=${DBTYPE}://sites/default/files/.ht.sqlite --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com 
      ;;
      mysql|mariadb)
        DBTYPE="mysql"
        ${DRUSH} si -v -y --db-url=${DBTYPE}://${DBUSER}:${DBPASS}@${DB_PORT_3306_TCP_ADDR}/${DCI_IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
      ;;
      pgsql)
        ${DRUSH} si -v -y --db-url=${DBTYPE}://${DBUSER}:${DBPASS}@${DB_PORT_5432_TCP_ADDR}/${DCI_IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
      ;;
    esac
    ${DRUSH} -y en simpletest
    EXTRA=""
fi
# We are going to write into files make sure it exist:
mkdir -p /var/www/sites/default/files/  /var/www/sites/simpletest
chown -fR www-data /var/www/sites/default/files/ /var/www/sites/simpletest

# Run the test suite.
echo ""
echo "Operation [run tests]..."
echo "export TERM=linux && cd /var/www && ${RUNSCRIPT} ${EXTRA} ${TESTGROUPS} | tee /var/www/test.stdout"
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${RUNSCRIPT} ${EXTRA} ${TESTGROUPS} | tee /var/www/test.stdout"

#No ugly xml please:
#for i in $(ls results/* ); do tidy -xml -m -i -q "$i"; done
