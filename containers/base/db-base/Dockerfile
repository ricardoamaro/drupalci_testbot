FROM debian:wheezy

ENV DEBIAN_FRONTEND noninteractive

# Packages.
RUN sed -i 's|http://http.debian.net/debian|ftp://ftp.debian.org/debian|g' /etc/apt/sources.list
RUN apt-get -y update

