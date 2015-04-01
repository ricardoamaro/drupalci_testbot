FROM       ubuntu:trusty
MAINTAINER drupalci

##
# Base.
##

ENV DEBIAN_FRONTEND noninteractive

##
# Use a local mirror
##

RUN echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt trusty main restricted universe multiverse' > /etc/apt/sources.list; \
echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt trusty-updates main restricted universe multiverse' >> /etc/apt/sources.list; \
echo 'deb mirror://mirrors.ubuntu.com/mirrors.txt trusty-security main restricted universe multiverse' >> /etc/apt/sources.list; \
echo 'deb-src mirror://mirrors.ubuntu.com/mirrors.txt trusty main restricted universe multiverse' >> /etc/apt/sources.list; \
echo 'deb-src mirror://mirrors.ubuntu.com/mirrors.txt trusty-updates main restricted universe multiverse' >> /etc/apt/sources.list; \
echo 'deb-src mirror://mirrors.ubuntu.com/mirrors.txt trusty-security main restricted universe multiverse' >> /etc/apt/sources.list;

##
# Remove Docs
##

# we need to keep copyright files for legal reasonsdd
# lintian stuff is small, but really unnecessary
RUN echo 'path-exclude /usr/share/doc/*' > /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-include /usr/share/doc/*/copyright' >> /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-exclude /usr/share/man/*' >> /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-exclude /usr/share/groff/*' >> /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-exclude /usr/share/info/*' >> /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-exclude /usr/share/lintian/*' >> /etc/dpkg/dpkg.cfg.d/01_nodoc \
    echo 'path-exclude /usr/share/linda/*' >> /etc/dpkg/dpkg.cfg.d/01_nodoc
##
# Pull fresh repo list
##

RUN apt-get clean && apt-get update

##
# install needed packages
##

RUN apt-get install -y unzip vim-tiny wget

##
# Remove unecessary packages and clean
##

RUN apt-get autoremove -y && apt-get clean
