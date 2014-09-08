FROM drupalci/web-base

RUN php-build -i development --pear 5.6.0 $HOME/.phpenv/versions/5.6.0
RUN phpenv rehash
RUN phpenv global 5.6.0


CMD ["/bin/bash", "/start.sh"]
