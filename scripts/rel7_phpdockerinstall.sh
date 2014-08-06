#!/bin/bash -xe

subscription-manager repos --enable=rhel-7-server-extras-rpms
yum -y install php-cli docker
yum update -y
service docker start
chkconfig docker on


