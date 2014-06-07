#!/bin/bash

if [ ! -f /var/lib/mysql/ibdata1 ];
    then
    echo "rebuilding /var/lib/mysql/ibdata1"
    mysql_install_db
    /usr/bin/mysqld_safe &
    PID="${!}"
    sleep 5s
    while ! netcat -vz localhost 3306; do sleep 1; done
    mysql -e "CREATE USER 'drupaltestbot'@'%' IDENTIFIED BY 'drupaltestbotpw';"
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'drupaltestbot'@'%' WITH GRANT OPTION; SELECT User FROM mysql.user; FLUSH PRIVILEGES;"
    echo "Grants added"
    killall mysqld
    wait ${PID}
fi

/usr/bin/mysqld_safe;
echo "mysql died at $(date)";

