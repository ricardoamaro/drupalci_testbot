FROM       drupalci/mod-php
MAINTAINER drupalci

##
# PHP 5.5.15
##

RUN sudo php-build -i development --pear 5.5.15 $HOME/.phpenv/versions/5.5.15
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global 5.5.15
