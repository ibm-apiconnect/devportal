#!/bin/bash -xe

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
# if change the project name then also need to update the jenkins docker image webroot

BUILD_DIR="$BASEDIR/build"
TMP_DIR="$BASEDIR/tmp"

docker build $* -t portal-with-site .

rm -rf $TMP_DIR/behat $TMP_DIR/watchdog
if [ ! -d $TMP_DIR ]; then
  mkdir $TMP_DIR
fi

mkdir $TMP_DIR/behat $TMP_DIR/watchdog
docker run -v $TMP_DIR/behat:/tmp/behat -v $TMP_DIR/watchdog:/tmp/watchdog --rm portal-with-site /tmp/run_tests.sh
