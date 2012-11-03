#!/bin/sh
while inotifywait -e modify * -e modify ../*.php; do
	timeout 10 phpunit --coverage-text .
done
