#/bin/bash

if [ ! -f /var/lib/mysql/ibdata1 ]; then

    mysql_install_db

    /usr/bin/mysqld_safe &
    sleep 10s

    echo "GRANT ALL ON *.* TO drupaltestbot@'%' IDENTIFIED BY 'drupaltestbotpw' WITH GRANT OPTION; FLUSH PRIVILEGES" | mysql

    killall mysqld
    sleep 10s
fi

while true; 
  do /usr/bin/mysqld_safe; 
    echo "mysql died at $(date)";
    sleep 1; 
  done

