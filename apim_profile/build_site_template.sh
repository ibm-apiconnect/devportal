#!/bin/bash -xe

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
# if change the project name then also need to update the jenkins docker image webroot

BUILD_DIR="$BASEDIR/build"
TMP_DIR="$BASEDIR/tmp"

docker build $* -t portal-site-template-with-site .

echo "Copying the site template out of the temporary container"
docker run --rm portal-site-template-with-site tar cf - -C /opt/ibm/templates . | tar xfv - -C $BUILD_DIR
