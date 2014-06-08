#!/bin/bash

if [ ! -f /var/lib/mysql/ibdata1 ];
    then
    echo "rebuilding /var/lib/mysql/ibdata1"
    mysql_install_db
    /usr/bin/mysqld_safe &
    PID="${!}"
    sleep 5s
    while ! netcat -vz localhost 3306; do sleep 1; done
    echo "GRANT ALL ON *.* TO drupaltestbot@'%' IDENTIFIED BY 'drupaltestbotpw' WITH GRANT OPTION; SELECT User FROM mysql.user; FLUSH PRIVILEGES;" | mysql
    echo "Grants added"
    killall mysqld
    wait ${PID}
fi

/usr/bin/mysqld_safe;
echo "mysql died at $(date)";

