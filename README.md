#PHP-HaloAPI v1.0.3-beta

**IMPORTANT: This wrapper is discontinued but you can still use it (some adaptations may be necessary as the API has changed over time).**

**This is a beta project (since API is still in beta)**

## Presentation
This class has for purpose to simplify the work of PHP developers who wants to use the official Halo 5 API (beta).

## Requirements
* Halo API key  (https://developer.haloapi.com/)

## Installation

Simply download and implement the class in your project.

You can also install it through composer:

```
composer require bananassplitter/php-haloapi
```

_Additional information: as the package is still in beta, be sure to set your minimum-stability to "beta"_

## Usage

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
This project is under **WTFPL** licence. See licence file.

## Changelog
* Moving API key to a class's initialization parameter
* Creating composer package
* Finalizing implementation of all metadatas calls
* No more needing of HTTP_Request2 class. The class now use curl.

## ToDo
Discontinued
