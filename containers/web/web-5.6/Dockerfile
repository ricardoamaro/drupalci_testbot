FROM drupalci/web-base

RUN CFLAGS="-g" time php-build -i development --pear 5.6.0 $HOME/.phpenv/versions/5.6.0
RUN $HOME/.phpenv/bin/phpenv rehash
RUN $HOME/.phpenv/bin/phpenv global 5.6.0
RUN . $HOME/.bashrc; php --version | grep ^PHP

CMD ["/bin/bash", "/start.sh"]
