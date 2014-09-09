FROM drupalci/web-base

ENV DEBIAN_FRONTEND noninteractive

# Squeeze
RUN echo "deb http://ftp.debian.org/debian/ squeeze main contrib non-free" >> /etc/apt/sources.list.d/squeeze.list 
RUN echo "deb http://security.debian.org/ squeeze/updates main contrib non-free" >> /etc/apt/sources.list.d/squeeze.list

# PIN PHP5.3 

RUN echo -e "\nPackage: php5*\nPin: release a=oldstable\nPin-Priority: 700\n" >> /etc/apt/preferences.d/preferences
RUN echo -e "\nPackage: libapache2-mod-php5\nPin: release a=oldstable\nPin-Priority: 700\n" >> /etc/apt/preferences.d/preferences
RUN echo -e "\nPackage: php-pear\nPin: release a=oldstable\nPin-Priority: 700\n" >> /etc/apt/preferences.d/preferences
RUN echo -e "\nPackage: php-apc\nPin: release a=oldstable\nPin-Priority: 700\n" >> /etc/apt/preferences.d/preferences
RUN echo -e "\nPackage: *\nPin: release a=stable\nPin-Priority: 600" >> /etc/apt/preferences.d/preferences

# Solve conflicts
RUN mv -f /etc/php5/conf.d/suhosin.ini /tmp/suhosin.bak
RUN mv -f /etc/php5/conf.d/apc.ini /tmp/apc.bak
RUN dpkg --remove php-apc

# Downgrade to php5.3
RUN apt-get update
RUN apt-get install -y --force-yes --reinstall libapache2-mod-php5 php5-curl php5-gd php5-mysql php5-sqlite php5-pgsql php-pear php-codesniffer php5 php5-suhosin php-apc

RUN apt-get clean
RUN apt-get -y autoremove

RUN mv -f /tmp/suhosin.bak /etc/php5/conf.d/suhosin.ini
RUN mv -f /tmp/apc.bak /etc/php5/conf.d/apc.ini
RUN find /etc/php5/conf.d -type l -exec rm {} \;

CMD ["/bin/bash", "/start.sh"]
