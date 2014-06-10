#!/bin/sh

while true; do
	inotifywait -e modify * -e modify ../*.php
	echo "something happened"
	phpunit .
done