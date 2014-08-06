#!/bin/bash -xe

apt-get -y install php5-cli
curl -s get.docker.io | sh 2>&1 | egrep -i -v "Ctrl|docker installed"

