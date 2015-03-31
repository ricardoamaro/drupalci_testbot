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
# Pull fresh repo list
##

RUN apt-get clean && apt-get update

##
# install needed packages
##

RUN apt-get install -y build-essential git unzip wget curl

##
# DEBUG packages
##
RUN apt-get install -y htop vim-tiny

##
# Remove unecessary packages and clean
##

RUN apt-get autoremove -y && apt-get clean
