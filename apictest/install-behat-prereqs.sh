#!/bin/bash
CURRENT_USER=$(whoami)
if [ $CURRENT_USER != 'root' ];
then
  echo "ERROR: Script must be run as root."
  return
fi

# the cpp installer will fail if this directory doesn't exist
mkdir /var/log

# potentially also need this line but needs retesting on a clean stack
# dpkg --configure cpp

# update package definitions or the install won't find anything to install
yum update -y

# install the required packages
yum groupinstall -y 'Development Tools'
