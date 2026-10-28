#!/bin/bash
# Installing the following modules to avoid phpstan errors on generated classes like:
#       "Class ... not found"
composer require --dev -W \
    "rector/rector ^2.0.0" \
    "phpstan/phpstan ^2.0" \
    "phpstan/extension-installer ^1.0" \
    "bitexpert/phpstan-magento >=0.43.0"
