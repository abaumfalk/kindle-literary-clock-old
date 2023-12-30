#!/bin/sh

# originally written by Jaap Meijers and published at 
# https://www.instructables.com/Literary-Clock-Made-From-E-reader/
# licensed under CC-BY-NC-SA https://creativecommons.org/licenses/by-nc-sa/4.0/
# (see licenses folder)

# if the Kindle is not being used as clock, then just quit
test -f /mnt/us/timelit/clockisticking || exit


# find the current minute of the day
MinuteOTheDay="$(TZ='CET-1' date -R +"%H%M")";

# check if there is at least one image for this minute 
lines="$(find /mnt/us/timelit/images/quote_$MinuteOTheDay* 2>/dev/null | wc -l)"
if [ $lines -eq 0 ]; then
	echo 'no images found for '$MinuteOTheDay
	exit
else
	echo $lines' files found for '$MinuteOTheDay
fi


# randomly pick a png file for that minute (since we have multiple for some minutes)
ThisMinuteImage=$(find /mnt/us/timelit/images/quote_$MinuteOTheDay* 2>/dev/null | shuf -n 1)

echo $ThisMinuteImage > /mnt/us/timelit/clockisticking

# clear the screen
eips -c

# show that image
eips -g $ThisMinuteImage
