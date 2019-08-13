#!/bin/bash

# Run:
# ./rununittests.sh

export IBM_MODULE_LIST="ibm_apim auth_apic apic_app apic_api product consumerorg featuredcontent ghmarkdown mail_subscribers socialblock themegenerator"

if [[ ! -d /tmp/test_results ]]
then
  mkdir /tmp/test_results
  chown aegir:aegir /tmp/test_results
fi

cd $APICTEST_BASEDIR/core
for MODULE_TO_UNITTEST in $IBM_MODULE_LIST
do
  echo "Running unit tests for $MODULE_TO_UNITTEST"
  echo "Command being executed is: $APICTEST_BASEDIR/vendor/bin/phpunit $APICTEST_BASEDIR/modules/$MODULE_TO_UNITTEST/tests/src/Unit/ --log-junit /tmp/test_results/$MODULE_TO_UNITTEST.phpunit.xml"
  $APICTEST_BASEDIR/vendor/bin/phpunit $APICTEST_BASEDIR/modules/$MODULE_TO_UNITTEST/tests/src/Unit/ --log-junit /tmp/test_results/$MODULE_TO_UNITTEST.phpunit.xml
done

RESULT=$?

echo "Unit test run finished"
exit $RESULT

