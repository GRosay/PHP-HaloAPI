<?php
/**
 *
 * PHP-HaloAPI
 * v 1.0.0-beta
 *
 * Composer example page
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * WTPFL Licence
 *
 */

require_once __DIR__ . '/vendor/autoload.php'; // Path to autoload file...

use PHPHaloApi\haloapi; // namespace and class name
$sApiKey = "xxxx"; // Use your API key - you can also use a constant.

$aPlayerNames = array('BananasSplitter', 'SatanicGeek'); // Creating an array of player.
$sApiKey = "****";
$oHaloApi = new haloapi($sApiKey, $aPlayerNames); // Initializing the class


$sEmblemUrl = $oHaloApi->getEmblem(512); // Get emblem img - size of 512

echo "<img src='$sEmblemUrl' />";

$oJson = $oHaloApi->getServiceRecords("campaign"); // Get service records for campaign

echo "<pre>".print_r($oJson, 1)."</pre>";

