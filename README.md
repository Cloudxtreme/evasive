evasive
=======

[![Latest Stable Version](https://poser.pugx.org/aporat/evasive/version.png)](https://packagist.org/packages/aporat/evasive) [![Composer Downloads](https://poser.pugx.org/aporat/evasive/d/total.png)](https://packagist.org/packages/aporat/evasive)

[![Build Status](https://travis-ci.org/aporat/evasive.png?branch=master)](https://travis-ci.org/aporat/evasive) [![Dependency Status](https://www.versioneye.com/user/projects/529f708e632bac512c000002/badge.png)](https://www.versioneye.com/user/projects/521b6fd1632bac7a5900b02a) [![Coverage Status](https://coveralls.io/repos/aporat/evasive/badge.png)](https://coveralls.io/r/aporat/evasive)

PHP library that provides evasive action in the event of an HTTP DoS or DDoS attack or brute force attack.

## Requirements ##

* PHP >= 5.4

## Getting Started ##

The easiest way to work with this package is when it's installed as a
Composer package inside your project. Composer isn't strictly
required, but makes life a lot easier.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

1. Add reply-me-by-email to your application's composer.json.

        {
            ...
            "require": {
                "aporat/evasive": "dev-master"
            },
            ...
        }

2. Run `php composer install`.

3. If you haven't already, add the Composer autoload to your project's
   initialization file. (example)

        require 'vendor/autoload.php';


## Quick Example ##


```php

$phpEvasive = new Evasive('Session', []);
$phpEvasive->defend();

```