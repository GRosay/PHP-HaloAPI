<?php
/**
 *
 * PHP-HaloAPI
 * v 1.0.3-beta
 *
 * Example page
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * WTPFL Licence
 *
 */

error_reporting(E_ALL);

require_once('haloapi.class.php');

$sApiKey = "****";
$oHaloApi = new haloapi($sApiKey, ['BananasSplitter', 'SatanicGeek'], 'hw2'); // Initializing the class


$sEmblemUrl = $oHaloApi->getAppearance();

