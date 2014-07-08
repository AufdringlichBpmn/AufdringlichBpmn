#!/bin/sh

while true; do
	inotifywait -e modify test/* -e modify src/*.php  -e modify src/elements/*.php
	echo "something happened"
	phpunit --include-path src test/. && php build.php
done