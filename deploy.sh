#!/bin/sh
export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git describe --always`
DIRTY=""
git status | grep -qF 'working directory clean' || DIRTY='-dirty'
echo "$BRANCH-$COMMIT$DIRTY" >version.txt
