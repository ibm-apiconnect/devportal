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

# We need to export this here so that the behat tests can see the db_enckey, as they do not actually run via nginx and php-fpm
db_enckey=$(grep "db_enckey " /var/aegir/config/server_master/nginx/vhost.d/127.0.0.1  | awk '{print $3}' | sed 's/;$//')
export db_enckey=$db_enckey

echo "Command to run : ${APICTEST_PREFIX} $APICTEST_BASEDIR/vendor/bin/behat $APICTEST_BEHAT_OPTS --config=$APICTEST_BASEDIR/behat.yml $PROFILE $TAGS $@"
${APICTEST_PREFIX} $APICTEST_BASEDIR/vendor/bin/behat $APICTEST_BEHAT_OPTS --config=$APICTEST_BASEDIR/behat.yml $PROFILE $TAGS $@

RESULT=$?

echo "Test run finished"
exit $RESULT
