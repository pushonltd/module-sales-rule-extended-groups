#!/bin/bash
# Installing the following modules to avoid phpstan errors on generated classes like:
#       "Class ... not found"
composer require --dev phpstan/extension-installer:^1.0 --no-update
composer require --dev bitexpert/phpstan-magento:^0.32 --no-update
