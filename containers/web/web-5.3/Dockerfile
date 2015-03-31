FROM       drupalci/mod-php
MAINTAINER drupalci

##
# PHP 5.3.29
##

RUN sudo php-build -i development --pear 5.3.29 $HOME/.phpenv/versions/5.3.29
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global 5.3.29

