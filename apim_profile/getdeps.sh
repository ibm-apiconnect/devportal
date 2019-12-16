#!/bin/bash -xe

SOURCE="${BASH_SOURCE[0]}"
DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

TMP_DIR=$1
SERVICE=$2
RELEASE=$3
GETANALYTICS=$4

cd ${DIR}
mkdir -p ${TMP_DIR}
cd ${TMP_DIR}
mkdir -p ${TMP_DIR}/explorer
mkdir -p ${TMP_DIR}/analytics
if [[ "${SERVICE}" == "true" ]]
then
  if [[ "${GETANALYTICS}" != "false" ]]
  then
    cd ${TMP_DIR}/analytics
    echo "Downloading @apic/analytics-native-vis@apic-v${RELEASE} node module from: https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/"
    npm config set @apic:registry https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/
    npm pack @apic/analytics-native-vis@latest
  fi
  cd ${TMP_DIR}/explorer
  echo "Downloading apiconnect-explorer node module from: npmjs/"
  npm pack apiconnect-explorer
else
  if [[ "${GETANALYTICS}" != "false" ]]
  then
    cd ${TMP_DIR}/analytics
    npm config set @apic:registry https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/
    echo "Downloading @apic/analytics-native-vis node module from: https://na.artifactory.swg-devops.com/artifactory/api/npm/apic-prod-npm/"
    npm --verbose pack @apic/analytics-native-vis@latest
  fi
  cd ${TMP_DIR}/explorer
  echo "Downloading apiconnect-explorer node module from: npmjs/"
  npm pack apiconnect-explorer
fi

cd ${TMP_DIR}/explorer
tar -zxf apiconnect-explorer*.tgz
if [[ "${GETANALYTICS}" != "false" ]]
then
  cd ${TMP_DIR}/analytics
  tar -zxf apic-analytics-native-vis*.tgz
fi
