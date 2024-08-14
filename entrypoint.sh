#!/bin/sh

# Start PHP built-in server in the background
php -S localhost:8000 -t src &

# Run the worker script
php src/worker.php
