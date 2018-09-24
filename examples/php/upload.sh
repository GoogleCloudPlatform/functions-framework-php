#!/bin/bash

set -e

if [[ -z "$1" ]]; then
  echo "Missing argument: ./upload.sh <GCS-bucket>"
  exit 1
fi

zip -r test-function.zip index.php
gsutil cp test-function.zip "gs://$1/php/test-function.zip"
rm test-function.zip
