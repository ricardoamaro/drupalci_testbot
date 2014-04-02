#!/bin/bash
#
# Name:         provision.sh
#
# Purpose:      quick start the vagrant box with all the things 
#
# Comments:          
#
# Usage:        vagrant up (on the repo root)
#
# Author:       Ricardo Amaro (mail@ricardoamaro.com)
# Contributors: Jeremy Thorson jthorson
#           
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-infrastructure
# 
# Docs:         README.md for complete information
#

export HOME="/home/vagrant"

env

if [ -f /home/vagrant/modernizing_testbot__dockerfiles/.git/config ];
then
	echo "You seem to have this box installed"
	echo "I'll just give you a shell..."
	cd /home/vagrant
	cd modernizing_testbot__dockerfiles
	git pull
	./build_all.sh update
else 
	echo "Installing and building the all thing..."
	echo "on: $(hostname) with user: $(whoami) home: $HOME"
	apt-get update
	apt-get install -y git mc ssh gawk grep sudo 
	cd /home/vagrant
	git clone https://github.com/ricardoamaro/modernizing_testbot__dockerfiles.git
	cd modernizing_testbot__dockerfiles
	./build_all.sh cleanup
fi 

chown -fR vagrant:vagrant /home/vagrant >/dev/null
echo "Box started, run vagrant halt to stop."
echo 
echo "To access the box and run tests, do:"
echo "vagrant ssh"
echo "cd modernizing_testbot__dockerfiles"
#echo 'Example: sudo TESTGROUPS="Bootstrap" DRUPALBRANCH="8.x" PATCH="/path/inthebox/to/your.patch,." ./run.sh'
