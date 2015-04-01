FROM       drupalci/web-base
MAINTAINER drupalci

##
# PHP 5.6.0
##

RUN sudo php-build -i development --pear 5.6.0 $HOME/.phpenv/versions/5.6.0
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global 5.6.0

COPY daemon.sh /
ENTRYPOINT ["/bin/bash", "-c"]
CMD ["/daemon.sh"]
