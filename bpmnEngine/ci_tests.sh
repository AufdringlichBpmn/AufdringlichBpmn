#!/bin/sh

while true; do
	inotifywait -e modify test/* -e modify src/*.php -e modify src/*/*.php
	php build.php
	phpunit --include-path src test/.
done