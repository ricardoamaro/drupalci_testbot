#!/bin/bash -e

# Check if we have root powers
if [ `whoami` != root ]; then
  echo "Please run this script as root or using sudo"
  exit 1
fi

apt-get -y install php5-cli
curl -s get.docker.io | sh 2>&1 | egrep -i -v "Ctrl|docker installed"

