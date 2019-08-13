#!/bin/bash

# Run:
# ./runbehat.sh <-t tags> <-p profile> <feature/scenario>

# ensure setupbehat.sh has been run. Required env vars are:
#APICTEST_BASEDIR
#APICTEST_BEHAT_OPTS

if [[ -z "$APICTEST_BASEDIR" || -z "$APICTEST_BEHAT_OPTS" ]]
then
  echo "ERROR: set up script has not been run. Please run: . ./setupbehat.sh <URL>"
  # TODO - source script here?
  exit 1
fi

TAGS=""
PROFILE=""

while getopts ":t:p:" opt; do
  case $opt in
    t)
      TAGS=" --tags=$OPTARG "
      ;;
    p)
      PROFILE=" --profile=$OPTARG "
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done

# move on to the rest of the command line options, i.e. $@
shift $((OPTIND-1))

echo "Command to run : ${APICTEST_PREFIX} $APICTEST_BASEDIR/vendor/bin/behat $APICTEST_BEHAT_OPTS --config=$APICTEST_BASEDIR/behat.yml $PROFILE $TAGS $@"
scl enable rh-python36 rh-php72 -- ${APICTEST_PREFIX} $APICTEST_BASEDIR/vendor/bin/behat $APICTEST_BEHAT_OPTS --config=$APICTEST_BASEDIR/behat.yml $PROFILE $TAGS $@

RESULT=$?

echo "Test run finished"
exit $RESULT
