<?php
/**
 *
 * PHP-HaloAPI
 * v 4.0.0
 *
 * Example page
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * Apache2.0-licence
 *
 */

error_reporting(E_ALL);

require_once('haloapi.class.php');

$sApiKey = "***";
$oHaloApi = new haloapi($sApiKey, ['BananasSplitter', 'SatanicGeek'], 'h5'); // Initializing the class


$sEmblemUrl = $oHaloApi->getEmblem(512); // Get emblem img - size of 512

echo "<img src='$sEmblemUrl' />";

$oJson = $oHaloApi->getServiceRecords("campaign"); // Get service records for campaign

echo "<pre>".print_r($oJson, 1)."</pre>";
