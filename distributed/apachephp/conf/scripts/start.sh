#!/bin/bash -e

#GET ALL INFO FROM /var/www/test.info:
source /var/www/test.info

export PATH=$HOME/bin:$PATH
export DRUSH="/.composer/vendor/drush/drush/drush"

# Start apache
echo "Operation [start]..."
apachectl start 2>/dev/null

echo "Operation [install]..."
#For now we use Drush to install the site but we are going to
#move to other real browser installer

#TODO: OTHER http://drush.ws/#site-install
cd /var/www/
echo ""

#Get the dependecies
#if [[ $DEPENDENCIES = "" ]]
#  then
#    echo -e "\$DEPENDENCIES has no modules declared...\n"
#  else
#    for DEP in $(echo "$DEPENDENCIES" | tr "," "\n")
#      do 
#      echo "Project: $DEP"
#      ${DRUSH} -y dl ${DEP}
#    done  
#    echo ""
#fi

if [[ $DBTYPE = "sqlite" ]]
  then
    ${DRUSH} si -y --db-url=sqlite://sites/default/files/.ht.sqlite --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
  else
    ${DRUSH} si -y --db-url=mysql://${DBUSER}:${DBPASS}@${DB_PORT_3306_TCP_ADDR}/${IDENTIFIER} --clean-url=0 --strict=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com
fi

${DRUSH} -y en simpletest

# Run the test suite.
echo ""
echo "Operation [run tests]..."
sudo -E -u www-data -H sh -c "export TERM=linux && cd /var/www && ${RUNSCRIPT}"
