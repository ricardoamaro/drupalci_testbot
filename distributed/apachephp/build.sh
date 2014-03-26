#!/bin/bash -ex

PHP=$1
if [[ $PHP = "" ]]; then {
echo "This will create a docker container for the testbot-web"
echo "You need to supply the php version."
echo "Available options:"
echo "sudo ./build.sh 5.3   - creates a PHP5.3 Version"
echo "sudo ./build.sh 5.4   - creates a PHP5.4 Version"
echo "sudo ./build.sh 5.5   - creates a PHP5.5 Version"
echo "DEFAULTING TO PHP5.4"
} fi

case $PHP in
  5.3) echo "Building PHP5.3 drupal/testbot-web53"
    VER="53"
    ;;
  5.5) echo "Building PHP5.5 drupal/testbot-web55"
    VER="55"
    ;;
  *) echo "Building PHP5.4 drupal/testbot-web54"
    VER="54"
    ;;
esac

mkdir -p /tmp/php${VER}/
cp -r conf/ /tmp/php${VER}/conf/
cp Dockerfile-PHP${VER} /tmp/php${VER}/Dockerfile
time docker build -t drupal/testbot-web${VER} /tmp/php${VER}/.
rm -rf /tmp/php${VER}/



exit 0
