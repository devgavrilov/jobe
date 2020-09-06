# Jobe-in-a-box: a Dockerised Jobe server (see https://github.com/trampgeek/jobe)
# With thanks to David Bowes (d.h.bowes@herts.ac.uk) who did all the hard work
# on this originally.

FROM ubuntu:18.04

LABEL maintainers="richard.lobb@canterbury.ac.nz,j.hoedjes@hva.nl"
ARG TZ=Pacific/Auckland
ARG ROOTPASS=jobeisfab
# Set up the (apache) environment variables
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
ENV LANG C.UTF-8

# Copy apache virtual host file for later use
COPY 000-jobe.conf /
# Copy test script
COPY container-test.sh /

# Set timezone
# Install extra packages
# Redirect apache logs to stdout
# Configure apache
# Configure php
# Setup root password
# Get and install jobe
# Clean up
RUN ln -snf /usr/share/zoneinfo/"$TZ" /etc/localtime && \
    echo "$TZ" > /etc/timezone && \
    apt-get update && \
    apt-get --no-install-recommends install -yq acl \
      apache2 \
      build-essential \
      fp-compiler \
      git \
      libapache2-mod-php \
      nodejs \
      octave \
      openjdk-8-jdk \
      php \
      php-cli \
      php-cli \
      php-mbstring \
      pylint3 \
      python3 \
      python3-pip \
      sqlite3 \
      sudo \
      tzdata \
      unzip && \
    pylint3 --reports=no --score=n --generate-rcfile > /etc/pylintrc && \
    ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/1 /var/log/apache2/error.log && \
    sed -i -e "s/export LANG=C/export LANG=$LANG/" /etc/apache2/envvars && \
    sed -i -e "1 i ServerName localhost" /etc/apache2/apache2.conf && \
    sed -i 's/ServerTokens\ OS/ServerTokens \Prod/g' /etc/apache2/conf-enabled/security.conf && \
    sed -i 's/ServerSignature\ On/ServerSignature \Off/g' /etc/apache2/conf-enabled/security.conf && \
    rm /etc/apache2/sites-enabled/000-default.conf && \
    mv /000-jobe.conf /etc/apache2/sites-enabled/ && \
    sed -i 's/expose_php\ =\ On/expose_php\ =\ Off/g' /etc/php/7.2/cli/php.ini && \
    mkdir -p /var/crash && \
    echo "root:$ROOTPASS" | chpasswd && \
    echo "Jobe" > /var/www/html/index.html;

RUN apt-get update
RUN apt-get install -y wget && cd /usr/lib && \
    wget -q https://github.com/JetBrains/kotlin/releases/download/v1.3.61/kotlin-compiler-1.3.61.zip && \
    unzip kotlin-compiler-*.zip && \
    rm kotlin-compiler-*.zip && \
    rm -f kotlinc/bin/*.bat

ENV PATH $PATH:/usr/lib/kotlinc/bin

ENV ANDROID_HOME /sdk

RUN wget -q https://dl.google.com/android/repository/commandlinetools-linux-6200805_latest.zip && \
    unzip commandlinetools-linux-6200805_latest.zip -d ${ANDROID_HOME} && \
    rm commandlinetools-linux-6200805_latest.zip

RUN yes | ${ANDROID_HOME}/tools/bin/sdkmanager --sdk_root=${ANDROID_HOME} --licenses
RUN yes | ${ANDROID_HOME}/tools/bin/sdkmanager --sdk_root=${ANDROID_HOME} "platform-tools"

RUN chmod -R a=rwx ${ANDROID_HOME}

ENV PATH $PATH:${ANDROID_HOME}/platform-tools

RUN wget -q https://services.gradle.org/distributions/gradle-6.3-bin.zip && \
    mkdir /opt/gradle && \
    unzip -d /opt/gradle gradle-6.3-bin.zip && \
    rm gradle-6.3-bin.zip

ENV PATH $PATH:/opt/gradle/gradle-6.3/bin

COPY . /var/www/html/jobe

RUN apache2ctl start && \
    cd /var/www/html/jobe && chmod a=rwx ./install && ./install && \
    chown -R www-data:www-data /var/www/html && \
    apt-get -y autoremove --purge && \
    apt-get -y clean && \
    rm -rf /var/lib/apt/lists/*

# Expose apache
EXPOSE 80

# Healthcheck, minimaltest.py should complete within 2 seconds
HEALTHCHECK --interval=5m --timeout=2s \
    CMD python3 /var/www/html/jobe/minimaltest.py || exit 1

# Start apache
CMD "/var/www/html/jobe/start.sh"