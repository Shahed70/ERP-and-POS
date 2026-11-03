#!/bin/bash

set -e

# Production application directory
APPDIR="/home/CPANEL_USERNAME/my-software-app"

# Copy application files
/usr/bin/rsync -a --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='storage/' \
  --exclude='.cpanel.yml' \
  ./ "$APPDIR/"

echo "Laravel files deployed successfully."
