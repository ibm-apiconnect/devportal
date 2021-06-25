#!/usr/bin/env bash

# TODO - take args and combine into a single call

# Non-core projects (modules and themese).
PROJECT_LIST=$(drush pm-list --no-core --format=csv --fields=name,version | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")

NONCORE_MODULE_LIST=$(drush pm-list --format=csv --no-core --fields=name,version --type=module | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
ALL_THEME_LIST=$(drush pm-list --format=csv --no-core --fields=name,version --type=theme| sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")
CORE_MODULE_LIST=$(drush pm-list --format=csv --core --fields=name,version --type=module | sed 's/.*(\(.*\))/\1/' | grep -v "Name,Version")

