#!/usr/bin/env bash
# Installation script for LODSPeaKr (http://lodspeakr.org)
# Author: Alvaro Graves (alvaro@graves.cl)

lodspeakr_repository="git://github.com/timrdf/DataFAQs-lodspeakr.git"
home=`basename $lodspeakr_repository | sed 's/.git//'`

source ~/.bashrc
GIT=`which git`

if [ -z "$GIT" ]; then
  echo "git is required to continue installation. Please add git to your \$PATH."
  exit 1
fi

if [ ! -e "$home" ]; then
   $GIT clone lodspeakr_repository
   cd $home
   ./install.sh
else
   echo "There is already an existing directory called '$home'. Installation cancelled. Remove it first and try again."
fi
