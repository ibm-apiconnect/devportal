#!/bin/bash -xe

SOURCE="${BASH_SOURCE[0]}"
DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

TMP_DIR=$1
SERVICE=$2
RELEASE=$3

cd ${DIR}
mkdir -p ${TMP_DIR}
cd ${TMP_DIR}

echo "Downloading apiconnect-explorer@latest node module from: https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/"
npm --registry https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/ pack apiconnect-explorer@latest

tar -zxf apiconnect-explorer*.tgz