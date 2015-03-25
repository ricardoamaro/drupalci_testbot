FROM ubuntu:14.04.1
MAINTAINER nick_schuch
##
# Base.
##
ENV DEBIAN_FRONTEND noninteractive
##
# Use a local mirror
##
RUN sed -i 's/archive.ubuntu.com/mirror.pnl.gov/g' /etc/apt/sources.list
##
# Pull fresh repo list
##
RUN apt-get clean && apt-get update
##
# install needed packages
##
RUN apt-get install -y build-essential git unzip wget
##
# DEBUG packages
##
#RUN apt-get install -y htop man vim curl byobu
##
# Ruby.
##
RUN echo "gem: --no-rdoc --no-ri " > /etc/gemrc
RUN apt-get install -y ruby ruby-dev make
##
# Serverspec.
##
RUN gem install bundler -v 1.6.3
RUN gem install net-ssh -v 2.9.1
RUN gem install rspec-core -v 2.99.2
RUN gem install diff-lcs -v 1.2.5
RUN gem install rspec-expectations -v 2.99.2
RUN gem install rspec-mocks -v 2.99.2
RUN gem install rspec -v 2.99.0
RUN gem install rspec-its -v 1.0.1
RUN gem install highline -v 1.6.21
RUN gem install specinfra -v 1.27.3
RUN gem install serverspec -v 1.16.0
RUN gem install rake
##
# Remove unecessary packages and clean
##
RUN apt-get autoremove -y && apt-get clean