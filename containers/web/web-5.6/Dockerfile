FROM drupalci/web-base

RUN CFLAGS="-g" time php-build -i development --pear 5.6.0 /.phpenv/versions/5.6.0
RUN /.phpenv/bin/phpenv rehash
RUN /.phpenv/bin/phpenv global 5.6.0
RUN . $HOME/.bashrc; php --version | grep ^PHP

RUN apt-get -y remove php5-curl php5-dev php5-gd php5-mysql php5-pgsql php5-sqlite php5-common php5-cli php-pear php-console-table php-codesniffer php5-mongo
RUN apt-get clean
RUN apt-get autoclean
RUN apt-get -y autoremove

RUN ln -s /.phpenv/shims/php /usr/bin/php
RUN cp -f /etc/php5/cli/php.ini /.phpenv/versions/5.6.0/etc/php.ini

CMD ["/bin/bash", "/start.sh"]
