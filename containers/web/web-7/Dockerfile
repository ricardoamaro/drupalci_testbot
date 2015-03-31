FROM       drupalci/mod-php
MAINTAINER drupalci

##
# PHP.
##

RUN sudo php-build -i development --pear master $HOME/.phpenv/versions/master
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global master

