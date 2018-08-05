#!/bin/bash

# change the group owner of the all the things to 'careset'
chgrp careset * -R
# ensure that members of the careset group can read, and write to the files. Ensure that new files have the same permission with the sticky bit
chmod g+rw * -R
# add the +s permission, but only to the directories..
find ./ -type d | xargs chmod g+s
