#!/bin/sh

# Check if we have root powers
if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
fi

docker build -t drupal/testbot-mysql --rm=true .

