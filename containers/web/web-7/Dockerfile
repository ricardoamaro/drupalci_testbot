FROM       drupalci/web-base
MAINTAINER drupalci

##
# PHP master (7)
##

RUN sudo php-build -i development --pear master $HOME/.phpenv/versions/master
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global master

EXPOSE 80
CMD ["/bin/bash", "/start.sh"]
