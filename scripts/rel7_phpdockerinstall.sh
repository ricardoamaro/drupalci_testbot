#!/bin/bash -e

# Check if we have root powers
if [ `whoami` != root ]; then
  echo "Please run this script as root or using sudo"
  exit 1
fi

subscription-manager repos --enable=rhel-7-server-extras-rpms
yum -y install php-cli docker
yum update -y
service docker start
chkconfig docker on


