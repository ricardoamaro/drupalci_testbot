#!/bin/bash -e


# Check if we have root powers
if [ `whoami` != root ]; then
  echo "Please run this script as root or using sudo"
  exit 1
fi

#For Centos6
rpm -iUvh http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm | true
yum update -y
yum -y install docker-io php-cli
service docker start
chkconfig docker on

