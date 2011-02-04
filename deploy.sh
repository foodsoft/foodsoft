#!/bin/sh
export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git describe --always`
DIRTY=""
git status | grep -qF 'working directory clean' || DIRTY='-dirty'
umask 022 # just in case version.txt does not exist yet
echo "$BRANCH-$COMMIT$DIRTY" >version.txt
