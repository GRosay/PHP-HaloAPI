# PHP-HaloAPI v2.0.0-beta

**This is a beta project**

For now, only supports Halo 5. Support for Halo Wars 2 and Halo 5 PC will come later.

## Presentation
This class has for purpose to simplify the work of PHP developers who wants to use the official Halo API (beta).

## Requirements
* Halo API key  (https://developer.haloapi.com/)

## Installation

Simply download and implement the class in your project.

You can also install it through composer:

```
composer require bananassplitter/php-haloapi
```

_Additional information: as the package is still in beta, be sure to set your minimum-stability to "beta"_

## Usage

To use the class, simply add it to your PHP file and then initialize:

```PHP
$sApiKey = "xxxx"; // Use your API key - you can also use a constant.
require_once('haloapi.class.php');
$oApi = new haloapi($sApiKey, array('BananasSplitter')); // Initialize the class

...
```

See _example.php_ file for concrete example.

### Composer

To initialize the class with composer, proceed like following

```PHP
<?php
require_once __DIR__ . '/vendor/autoload.php'; // Path to autoload file...

use PHPHaloApi\haloapi; // namespace and class name
$sApiKey = "xxxx"; // Use your API key - you can also use a constant.

$oApi = new haloapi(sApiKey , array('BananasSplitter')); // Initialize the class

...

```

See _example.composer.php_ file for concrete example.

## Licence
This project is under **Apache 2.0** licence. See licence file.

## Changelog
* Adding all missing endpoints

