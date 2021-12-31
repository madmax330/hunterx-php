#!/bin/bash

echo "Removing old backup version..."

# Clear previous backup if it exists
sudo rm -rf /hunterx-php/backup/

echo "Old backup removed"
echo "Backing up current version..."

echo "Stopping apache"
sudo systemctl stop apache2

# Move old version to a backup folder in case we need to rollback
sudo mv /var/www/hunterx-php /hunterx-php/backup/

echo "Current version backed up"
echo "Getting latest version..."

# Download latest version from git
git clone git@github.com-hunterx-php:madmax330/hunterx-php.git /root/hunterx-php
sudo mv /root/hunterx-php /var/www

echo "Latest version installed"
echo "Restarting apache"

sudo systemctl start apache2

echo "Apache restarted"
echo "All done!"

sudo mv /var/www/hunterx-php/deploy.sh /root/deploy.sh