#!/bin/bash -e

#GET ALL INFO FROM /var/www/test.info:
source /var/www/test.info

export PATH=$HOME/bin:$PATH
# Only need the newest drush version for Drupal 8 and above
if (( $DCI_DRUPALVERSION >= 8 ));
  then 
      export DRUSH="/.composer/vendor/drush/drush/drush"
    else 
      export DRUSH="$(which drush)"
fi

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

if (( $DCI_DRUPALVERSION >= 8 )) && [[ $DCI_INSTALLER = "none" ]];
  then
    echo "DCI_DRUPALVERSION is $DCI_DRUPALVERSION"
    echo "Skipping operation [install], using core tester instead..."
    #Create drupal database manually because Drupal>=8
    case $DCI_DBTYPE in
      pgsql)
        export PGPASSWORD="${DCI_DBPASS}";
        export PGUSER="${DCI_DBUSER}";
        /usr/bin/psql -h ${DB_PORT_5432_TCP_ADDR} -w -c "DROP DATABASE IF EXISTS ${DCI_IDENTIFIER};"
        /usr/bin/psql -h ${DB_PORT_5432_TCP_ADDR} -w -c "CREATE DATABASE ${DCI_IDENTIFIER} OWNER ${DCI_DBUSER} TEMPLATE DEFAULT ENCODING='utf8';"
        DBADDR=${DB_PORT_5432_TCP_ADDR}
      ;;
      mysql|mariadb)
        DCI_DBTYPE="mysql"
        /usr/bin/mysql -u${DCI_DBUSER} -p${DCI_DBPASS} -h${DB_PORT_3306_TCP_ADDR} -e "CREATE DATABASE IF NOT EXISTS ${DCI_IDENTIFIER} ;"
        DBADDR=${DB_PORT_3306_TCP_ADDR}
      ;;
    esac
    EXTRA="--sqlite /var/www/test.sqlite --keep-results"
    if [ $DCI_DBTYPE = "mongodb" ]
    then
      cp modules/mongodb/drivers/mongodb/Install/settings.php sites/default/settings.testing.php
      sed "s/\[host\]/mongodb:\/\/${DB_PORT_27017_TCP_ADDR}:${DB_PORT_27017_TCP_PORT}/" /mongodb.settings.php > sites/default/settings.php
    else
      EXTRA="${EXTRA} --dburl ${DCI_DBTYPE}://${DCI_DBUSER}:${DCI_DBPASS}@${DBADDR}/${DCI_IDENTIFIER}"
    fi
  else
    echo "Operation $DCI_DRUPALVERSION [install] using drush... "
    case $DCI_DBTYPE in
      sqlite)
        ${DRUSH} si ${VERBO} -y --db-url=${DCI_DBTYPE}://sites/default/files/.ht.sqlite --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
      ;;
      mysql|mariadb)
        DCI_DBTYPE="mysql"
        ${DRUSH} si ${VERBO} -y --db-url=${DCI_DBTYPE}://${DCI_DBUSER}:${DCI_DBPASS}@${DB_PORT_3306_TCP_ADDR}/${DCI_IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
      ;;
      pgsql)
        export PGPASSWORD="${DCI_DBPASS}";
        export PGUSER="${DCI_DBUSER}";
        ${DRUSH} si ${VERBO} -y --db-url=${DCI_DBTYPE}://${DCI_DBUSER}:${DCI_DBPASS}@${DB_PORT_5432_TCP_ADDR}/${DCI_IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
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
echo "export TERM=linux && cd /var/www && ${DCI_RUNSCRIPT} ${EXTRA} ${DCI_TESTGROUPS} | tee /var/www/test.stdout"
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${DCI_RUNSCRIPT} ${EXTRA} ${DCI_TESTGROUPS} | tee /var/www/test.stdout"

#No ugly xml please:
#for i in $(ls results/* ); do tidy -xml -m -i -q "$i"; done
