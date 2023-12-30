#!/bin/sh

# originally written by Jaap Meijers and published at 
# https://www.instructables.com/Literary-Clock-Made-From-E-reader/
# licensed under CC-BY-NC-SA https://creativecommons.org/licenses/by-nc-sa/4.0/
# (see licenses folder)

# see what image is shown at the moment
current=$(cat clockisticking 2>/dev/null)

# only if a filename is in the clockisticking file, then continue 
if [ -n "$current" ]; 
	then

	# find the matching image with metadata
	currentCredit=$(echo $current | sed 's/.png//')_credits.png
	currentCredit=$(echo $currentCredit | sed 's/images/images\/metadata/')

	# show the image with metdata
	eips -g $currentCredit

fi

# start waiting for new keystrokes
/usr/bin/waitforkey 104 191 && /mnt/us/timelit/showMetadata.sh &
