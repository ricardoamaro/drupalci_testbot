#!/bin/bash -xe

yum -y install php-cli

#For Centos6
rpm -iUvh http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
yum update -y
yum -y install docker-io
service docker start
chkconfig docker on


#FOR RHE7
subscription-manager repos --enable=rhel-7-server-extras-rpms
yum -y install docker


