#!/bin/bash

# script for use in the jenkins Devportal - Run Behat tests job.
# Executes behat tests with different parameters.


# Update PROFILES if you are adding more profiles (top level config in behat.yml).
# format is PROFILES=(profile1 profile2 profile3)
PROFILES=(ldap)

TAGS=$1

# runBehat function - takes profile name as argument
function runBehat() {
  if [[ -z $TAGS ]]
  then
    TAGS_ARG=""
  else
    TAGS_ARG=" -t $TAGS "
  fi

  if [[ -z $1 ]]
  then
    PROFILE_ARG=""
  else
    PROFILE_ARG=" -p $1 "
  fi

  ./runbehat.sh $TAGS_ARG $PROFILE_ARG
}

## Script start

declare -i RESULT=0

# default invocation of behat
echo "Running behat - default invocation"
runBehat
RESULT+=$?

# run with profile parameter
for PROFILE in ${PROFILES[@]}; do
    echo "Running behat for profile: $PROFILE"
    runBehat $PROFILE
    RESULT+=$?
done

# echo something to be caught by the expect script.
if [[ $RESULT == 0 ]]
then
  echo "All tests passed"
else
  echo "Tests failed"
fi

exit $RESULT

