# CiviCRM Mailingwork Integration

[![Run unit tests](https://github.com/greenpeace-cee/at.greenpeace.mailingwork/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/greenpeace-cee/at.greenpeace.mailingwork/actions/workflows/unit-tests.yml)


This extension connects CiviCRM with [Mailingwork](https://mailingwork.de/), an email marketing tool.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP 7.2+
* CiviCRM (5.24+)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/greenpeace-cee/at.greenpeace.mailingwork.git
cd at.greenpeace.mailingwork
composer install
cv en mailingwork
```

## Usage

To use this extension, you need an existing Mailingwork account and API credentials.

## Known Issues

- Synchronization of Mailingwork lists/recipient data is not implemented
