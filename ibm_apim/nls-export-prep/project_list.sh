#!/usr/bin/env bash

# TODO - take args and combine into a single call

# Non-core projects (modules and themese).
MAIN_PROJECT_LIST=$(drush pm-list --no-core --format=csv --fields=name,version | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
PROJECT_LIST_SPACES=$(echo $MAIN_PROJECT_LIST | awk -F " " '{print NF}')

ONE_QUARTER_OF_LIST=$(($PROJECT_LIST_SPACES / 4))

SUB_PROJECT_LIST_ONE=$(echo $MAIN_PROJECT_LIST | cut -d " " -f1-$ONE_QUARTER_OF_LIST)
SUB_PROJECT_LIST_TWO=$(echo $MAIN_PROJECT_LIST | cut -d " " -f$(($ONE_QUARTER_OF_LIST + 1))-$(($ONE_QUARTER_OF_LIST * 2 )))
SUB_PROJECT_LIST_THREE=$(echo $MAIN_PROJECT_LIST | cut -d " " -f$(($ONE_QUARTER_OF_LIST * 2 + 1))-$(($ONE_QUARTER_OF_LIST * 3)))
SUB_PROJECT_LIST_FOUR=$(echo $MAIN_PROJECT_LIST | cut -d " " -f$(($ONE_QUARTER_OF_LIST * 3 + 1))-$PROJECT_LIST_SPACES)

NONCORE_MODULE_LIST=$(drush pm-list --format=csv --no-core --fields=name,version --type=module | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
ALL_THEME_LIST=$(drush pm-list --format=csv --no-core --fields=name,version --type=theme| sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
CORE_MODULE_LIST=$(drush pm-list --format=csv --core --fields=name,version --type=module | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
