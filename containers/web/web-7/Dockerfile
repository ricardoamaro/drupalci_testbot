FROM       drupalci/web-base
MAINTAINER drupalci

##
# PHP master (7)
##

RUN sudo php-build -i development --pear master $HOME/.phpenv/versions/master
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global master
# TODO: pecl mongo not working yet
# RUN echo | pecl install mongo

# Fix date.timezone warning
RUN find /root/.phpenv/ -iname php.ini -exec sh -c 'echo "date.timezone=UTC" >> {}' \;

EXPOSE 80
CMD ["/bin/bash", "/start.sh"]
