<?php
/**
 *
 * PHP-HaloAPI
 * v 0.0.1
 *
 * Exemple page
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * WPFL Licence
 *
 */

require_once 'HTTP/Request2.php'; // Necessary if you want the class to work :)
require_once('class/haloapi.class.php');

$aPlayerNames = array('BananasSplitter', 'Snipedown'); // Creating an array of player.

$oHaloApi = new haloapi($aPlayerNames); // Initializing the class


$sEmblemUrl = $oHaloApi->fGetEmblemImg(512); // Get emblem img - size of 512

echo "<img src='$sEmblemUrl' />";

$oJson = $oHaloApi->fGetServiceRecords("campaign"); // Get service records for campaign

echo "<pre>".print_r($oJson, 1)."</pre>";

