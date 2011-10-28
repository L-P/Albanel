#!/bin/bash
cd /var/www/nadyn

sudo true || exit
sudo chown -R leo:www-data .
sudo chmod -R ug+rw .
sudo find . -type d -exec chmod ug+rwx {} +


