FROM drupalci/php-base
MAINTAINER nick_schuch
##
# PHP.
##
RUN sudo php-build -i development --pear 5.4.31 $HOME/.phpenv/versions/5.4.31
RUN sudo chown -R root:root $HOME/.phpenv
RUN phpenv rehash
RUN phpenv global 5.4.31
#ENTRYPOINT ["travis", "run", "--skip-version-check", "--skip-completion-check"]

COPY daemon.sh /
ENTRYPOINT ["/bin/bash", "-c"]
CMD ["/daemon.sh"]