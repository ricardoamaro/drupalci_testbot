############################################################
# Dockerfile to add mariadb to centos-httpd image
# Based on jthorson/centos-httpd
############################################################
FROM jthorson/centos-httpd
MAINTAINER Jeremy Thorson

# Add mariadb repositories (/etc/yum.repos.d/mariadb.repo)
#------Content------
# MariaDB 5.5 CentOS repository list - created 2014-02-06 22:34 UTC
# http://mariadb.org/mariadb/repositories/
#[mariadb]
#name = MariaDB
#baseurl = http://yum.mariadb.org/5.5/centos6-amd64
#gpgkey=https://yum.mariadb.org/RPM-GPG-KEY-MariaDB
#gpgcheck=1
#------End Content------

RUN yum install -y MariaDB-server MariaDB-client && chkconfig mysql on && service mysql start

# Security
# OPTION 1:
# RUN /usr/bin/mysql_secure_installation 
# TODO: need to run this and provide input in one line
# OPTION 2:
# RUN mysqladmin -u root password [randompassword]
# TODO: Variable to store db password
# Create database, create mysql user, enable remote connections to database
#    DB_NAME = webdb
#    USER_NAME = webdb_user
#    REMOTE_IP = 10.0.15.25
#    PASSWORD = password123
#    PERMISSIONS = ALL
## CREATE DATABASE ##
#MariaDB [(NONE)]> CREATE DATABASE webdb;
## CREATE USER ##
#MariaDB [(NONE)]> CREATE USER 'webdb_user'@'10.0.15.25' IDENTIFIED BY 'password123';
## GRANT PERMISSIONS ##
#MariaDB [(NONE)]> GRANT ALL ON webdb.* TO 'webdb_user'@'10.0.15.25';
##  FLUSH PRIVILEGES, Tell the server TO reload the GRANT TABLES  ##
#MariaDB [(NONE)]> FLUSH PRIVILEGES;
#Enable Remote Connection to MariaDB Server –> Open MySQL Port (3306) on Iptables Firewall (as root user again)
#1. CentOS/Red Hat (RHEL)
#1.1 Edit /etc/sysconfig/iptables file:
#nano -w /etc/sysconfig/iptables
#1.2 Add following INPUT rule:
#-A INPUT -m state --state NEW -m tcp -p tcp --dport 3306 -j ACCEPT
#1.3 Restart Iptables Firewall:
#service iptables restart
## OR ##
#/etc/init.d/iptables restart

#http://www.if-not-true-then-false.com/2013/install-mariadb-on-fedora-centos-rhel/