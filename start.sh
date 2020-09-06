#!/bin/bash

adb connect host.docker.internal

rm -f /var/run/apache2/apache2.pid

/usr/sbin/apache2ctl -D FOREGROUND