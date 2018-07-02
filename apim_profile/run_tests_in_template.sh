#!/bin/bash -xe

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
# if change the project name then also need to update the jenkins docker image webroot

BUILD_DIR="$BASEDIR/build"
TMP_DIR="$BASEDIR/tmp"

docker build $* -t portal-site-template-with-site-tests test

rm -rf $TMP_DIR/behat $TMP_DIR/watchdog
mkdir $TMP_DIR/behat $TMP_DIR/watchdog
docker run -v $TMP_DIR/behat:/tmp/behat -v $TMP_DIR/watchdog:/tmp/watchdog --rm portal-site-template-with-site-tests /tmp/site-template_test.sh
