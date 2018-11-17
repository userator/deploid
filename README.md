# Deploid

[![Build Status](https://travis-ci.org/userator/deploid.svg?branch=master)](https://travis-ci.org/userator/deploid)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/userator/deploid/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/userator/deploid/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/userator/deploid/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/userator/deploid/?branch=master)

Tool provides folder structure for continuous delivery of code

## Installation

It's recommended that you use [composer](https://getcomposer.org/) to install Deploid.

### Using [composer](https://packagist.org/packages/userator/deploid)

```bash
$ composer require userator/deploid "1.0.0"
```

### Using [git](https://github.com/userator/deploid.git)

```bash
$ git clone https://github.com/userator/deploid.git
```

### Using [wget](https://github.com/userator/deploid/releases)

```bash
$ wget https://github.com/userator/deploid/archive/1.0.0.tar.gz
```

## Make PHAR personally

Run script:

```bash
php -d phar.readonly=0 ./build/make-phar.php
```

Archive will be maked in the same directory:

```bash
./build/deploid.phar
```

Deploid requires PHP 5.6.0 or newer.