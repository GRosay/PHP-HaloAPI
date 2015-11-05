<?php
/**
 *
 * PHP-HaloAPI
 * v 0.1.0
 *
 * Exemple page
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * WTPFL Licence
 *
 */

require_once('haloapi.class.php');

$aPlayerNames = array('BananasSplitter', 'SatanicGeek'); // Creating an array of player.

$oHaloApi = new haloapi($aPlayerNames); // Initializing the class


$sEmblemUrl = $oHaloApi->getEmblem(512); // Get emblem img - size of 512

echo "<img src='$sEmblemUrl' />";

$oJson = $oHaloApi->getServiceRecords("campaign"); // Get service records for campaign

echo "<pre>".print_r($oJson, 1)."</pre>";

