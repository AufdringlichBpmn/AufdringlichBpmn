#!/bin/sh

while true; do
	inotifywait -e modify * -e modify ../*.php  -e modify ../elements/*.php
	echo "something happened"
	phpunit .
done