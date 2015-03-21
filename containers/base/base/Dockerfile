FROM       ubuntu:12.04
MAINTAINER nick_schuch

##
# Base.
##

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get clean && apt-get update
RUN apt-get install -y build-essential software-properties-common byobu curl git htop man unzip vim wget python-software-properties

##
# Ruby.
##

RUN apt-add-repository ppa:brightbox/ruby-ng
RUN apt-get update
RUN apt-get install -y ruby2.0 ruby2.0-dev ruby2.0-doc
RUN rm -rf /var/lib/apt/lists/*

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


RUN apt-get clean && apt-get autoremove -y
