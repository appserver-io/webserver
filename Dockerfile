################################################################################
# Dockerfile for appserver.io webserver
################################################################################

# base image
FROM debian:jessie

# author
MAINTAINER Tim Wagner <tw@appserver.io>

################################################################################

# define versions
ARG APPSERVER_RUNTIME_BUILD_VERSION=1.1.6-44

################################################################################

# update the sources list
RUN apt-get update \

    # install the necessary packages
    && DEBIAN_FRONTEND=noninteractive apt-get install wget git curl -y

################################################################################

# download runtime in specific version
RUN wget -O /tmp/appserver-runtime.deb \
    http://builds.appserver.io/linux/debian/8/appserver-runtime_${APPSERVER_RUNTIME_BUILD_VERSION}~deb8_amd64.deb \

    # install runtime
    && dpkg -i /tmp/appserver-runtime.deb; exit 0

# install missing runtime dependencies
RUN apt-get install -yf \

    # remove the unnecessary .deb file
    && rm -f /tmp/appserver-runtime.deb \

    # create a symlink for the appserver.io PHP binary
    && ln -s /opt/appserver/bin/php /usr/local/bin/php

################################################################################

# clear apk cache to optimize image filesize
RUN rm -rf /var/cache/apk/*

################################################################################

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

################################################################################

# install the appserver.io webserver
RUN cd /opt && /opt/appserver/bin/composer create-project --no-dev appserver-io/webserver

################################################################################

# define working directory
WORKDIR /opt/webserver

# expose ports
EXPOSE 9080 9443

# start the webserver
CMD ["/opt/appserver/bin/php", "bin/webserver"]
