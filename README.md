# PHP-HaloAPI

[![Latest Stable Version](https://poser.pugx.org/bananassplitter/php-haloapi/v/stable)](https://packagist.org/packages/bananassplitter/php-haloapi)
[![Total Downloads](https://poser.pugx.org/bananassplitter/php-haloapi/downloads)](https://packagist.org/packages/bananassplitter/php-haloapi)
[![Latest Unstable Version](https://poser.pugx.org/bananassplitter/php-haloapi/v/unstable)](https://packagist.org/packages/bananassplitter/php-haloapi)
[![License](https://poser.pugx.org/bananassplitter/php-haloapi/license)](https://packagist.org/packages/bananassplitter/php-haloapi)

For now, only supports Halo 5 and Halo Wars 2. Support for Halo 5 PC will come later.

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
* 2018-10-03: Adding Halo Wars 2 endpoints + return error when title not supported
* Adding all missing endpoints

## Legal notice
This project is not supported/developed/affiliated in any way by/with Halo, 343 Industries or Microsoft. 

Halo is a game developed by 343 Industries. 
