FROM       drupalci/mod-php
MAINTAINER drupalci

##
# PHP.
##

RUN sudo php-build -i development --pear 5.4.31 $HOME/.phpenv/versions/5.4.31
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global 5.4.31

