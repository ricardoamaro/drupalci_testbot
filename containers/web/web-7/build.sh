#!/bin/bash

# Remove intermediate containers after a successful build. Default is True.
DCI_REMOVEINTCONTAINERS=${DCI_REMOVEINTCONTAINERS:-"true"}

# Check if we are a member of the "docker" group or have root powers
USERNAME=$(whoami)
TEST=$(groups $USERNAME | grep -c '\bdocker\b')
if [ $TEST -eq 0 ];
then
  if [ `whoami` != root ]; then
    echo "Please run this script as root or using sudo"
    exit 1
  fi
fi

docker build --rm=${DCI_REMOVEINTCONTAINERS} -t drupalci/web-7 .
