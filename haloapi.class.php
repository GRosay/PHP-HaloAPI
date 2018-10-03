<?php

/**
 *
 * PHP-HaloAPI
 * v 4.0.0-dev
 *
 * This class has for purpose to simplify the work of PHP developers who wants to use the official (beta) Halo API.
 *
 * Author: Gaspard Rosay - @BananasSplitter
 *
 * Apache-2.0 Licence
 *
 */

class haloapi
{

    const BASE_URL          = "https://www.haloapi.com/"; // Base url for API, may change on day...

    private $apiKey        = ""; // Will contain the API key
    private $title         = ""; // Correspond to the game title
    private $titles        = ["h5", "hw2"];
    private $playerNames   = array(); // List of users (functions may use only the first user)

    private $lastHeaders    = array(); // Array of parsed headers from the last API call
    public $lastApiVersion = ""; // X-343-Version header from the last API call

    public static $queryLimit = 10; // The number of queries that you are allowed to perform within the alloted time window.
    public static $queryWindowSecs = 10; // The time window on which queries are bound, in seconds.

    private static $queryCount = 0;
    private static $queryWindowStartTime;

    /**
     * @name __construct
     *
     * Initialize the class
     *
     * @param $playerNames: an array containing list of players
     * @param $title: the title concerned by the API - default: h5
     */
    function __construct($apiKey, $playerNames, $title = "h5"){
        if(!in_array($title, $this->titles)){
            trigger_error("The title " . $title." is not supported!", E_USER_ERROR);
            return false;
        }
        $this->apiKey = $apiKey;
        $this->playerNames = $playerNames;
        $this->title = $title;
    }

### Global functions

    /**
     * @param $url : url to use in API call
     *
     * @param null $lang
     * @return array $response: the API response
     */
    private function callAPI($url, $lang=null){
        self::throttle();

        $ch = curl_init();

        $httpheader = array('Ocp-Apim-Subscription-Key' => $this->apiKey);

        if(!is_null($lang)){
            $httpheader['Accept-Language'] = $lang;
        }

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER         => true,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => 'PHP-HaloAPI',
            CURLOPT_HTTPHEADER     => $httpheader
        ));

        $resp = curl_exec($ch);
        if(!$resp){
            die('Error in API call: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        else{
            // Then, after your curl_exec call:
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($resp, 0, $header_size);
            $body = substr($resp, $header_size);
        }
        curl_close($ch);

        // Parse the headers and store them in the lastHeaders class property
        $this->lastHeaders = $this->getHeaders($header);

        // Keep track of the API version in the lastApiVersion class property
        if(isset($this->lastHeaders['X-343-Version'])){
            $this->lastApiVersion = $this->lastHeaders['X-343-Version'];
        }

        return array('header' => $header, 'body' => $body);
    }


    /**
     * @name throttle
     *
     * Throttle API calls using the $queryLimit class property
     **
     * @return null
     */
    private static function throttle(){
        self::$queryCount++;

        if(self::$queryWindowStartTime === null){
            self::$queryWindowStartTime = new DateTime();
        }

        if(self::$queryCount > self::$queryLimit){
            $now = new DateTime();
            $diffSec = $now->getTimestamp() - self::$queryWindowStartTime->getTimestamp();

            // If we've exceeded the query count, and we still are within the query window, then wait.
            if($diffSec < self::$queryWindowSecs){
                sleep(self::$queryWindowSecs - $diffSec + 1);
            }

            // Then, once we've waited, if necessary, we'll assume that we're beyond the query window, so we'll start our throttling over.
            self::$queryWindowStartTime = new DateTime();
            self::$queryCount = 1;
        }
    }

    /**
     * @name getHeaders
     *
     * Return headers information from curl header
     *
     * @param $sHeader: header returned by curl
     *
     * @return $headers: array containing all headers infos
     */
    private function getHeaders($sHeader){

        $headers = array();

        $header_text = substr($sHeader, 0, strpos($sHeader, "\r\n\r\n"));

        foreach(explode("\r\n", $header_text) as $i => $line){
            if($i === 0){
                $headers['http_code'] = $line;
            }
            else{
                list($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * @name $this->decodeJson
     *
     * Return decoded string from json - return an error if json isn't correct
     *
     * @param $json: the encoded json string
     *
     * @return $json: the json string once decoded
     */
    private function decodeJson($json){

        // utf8_encode the json string only if the response charset was not set to utf-8 already
        if(!isset($this->lastHeaders['Content-Type']) || $this->lastHeaders['Content-Type'] != 'application/json; charset=utf-8'){
            $json = utf8_encode($json);
        }

        $json = iconv('UTF-8', 'UTF-8//IGNORE', $json);
        $json = json_decode($json);

        if(json_last_error() === JSON_ERROR_NONE){
            return $json;
        }
        else{
            return "ERROR JSON(".json_last_error()."): ".json_last_error_msg();
        }

    }
###

### Profile part

    /**
     * @name getAppearance
     *
     * Return Metadata for the Player-created Game Variant
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdc2e21091812784ce8c2/operations/5969689a2109180f287972a8?
     *
     * @return $oJson: json object containing player's Metadata
     */
    public function getAppearance(){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."profile/".$this->title."/profiles/".$this->playerNames[0]."/appearance";
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getEmblem
     *
     * Return the url of player's emblem img
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdc2e21091812784ce8c2/operations/58acdc2e2109180bdcacc404?
     *
     * @param $size: size wanted - default: null
     *
     * @return $aHeader['location']: url of the img
     */
    public function getEmblem($size = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."profile/".$this->title."/profiles/".$this->playerNames[0]."/emblem";

        if(!is_null($size)){
            $url.= "?size=".$size;
        }

        $response = $this->callAPI($url);
        $location = isset($this->lastHeaders['Location']) ? $this->lastHeaders['Location'] : false;

        return $location;
    }

    /**
     * @name getSpartanImg
     *
     * Return the url of player's spartan img
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdc2e21091812784ce8c2/operations/58acdc2e2109180bdcacc405?
     *
     * @param $size: size wanted - default: null
     *
     * @return $aHeader['location']: url of the img
     */
    public function getSpartanImg($size = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."profile/".$this->title."/profiles/".$this->playerNames[0]."/spartan";

        if(!is_null($size)){
            $url.= "?size=".$size;
        }

        $response = $this->callAPI($url);
        $location = isset($this->lastHeaders['Location']) ? $this->lastHeaders['Location'] : false;

        return $location;
    }
### End of profile part

### Metadata part

    /**
     * @name getCampaignMissions
     *
     * Return information of all campaign missions
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc421
     *
     * @return $oJson: json object containing campaign informations
     */
    public function getCampaignMissions($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/campaign-missions";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCommendations
     *
     * Return information about all commendations
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc422?
     *
     * @return $oJson: json object containing commendations data
     */
    public function getCommendations($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/commendations";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMetaCompayComendations
     *
     * Return information about all Company Commendations
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/Halo-5-Company-Commendations?
     *
     * @return $oJson: json object containing commendations data
     */
    public function getMetaCompayComendations($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/company-commendations";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCSRDesignations
     *
     * Return information about all CSR Designations
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc423?
     *
     * @return $oJson: json object containing csr designations data
     */
    public function getCSRDesignations($start = null, $lang = null){
        $url = self::BASE_URL."metadata/".$this->title. ( $this->title == 'h5' ? "/metadata/" : "/")."csr-designations".
            ($this->title != 'h5' && !is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getEnemies
     *
     * Return information about all enemies (IA)
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc424?
     *
     * @return $oJson: json object containing enemies data
     */
    public function getEnemies($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/enemies";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getFlexibleStats
     *
     * Return information about flexible stats
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc425?
     *
     * @return $oJson: json object containing flexible stats data
     */
    public function getFlexibleStats($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/flexible-stats";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getGameBaseVariants
     *
     * Return information about game base variants
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc426?
     *
     * @return $oJson: json object containing game base variants data
     */
    public function getGameBaseVariants($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/game-base-variants";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getGameVariantData
     *
     * Return information about the given game variant
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc427?
     *
     * @param $packId: ID of the game variant wanted
     *
     * @return $oJson: json object containing game variant data
     */
    public function getGameVariantData($variantId, $lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/game-variants/".$variantId;
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getImpulses
     *
     * Return information about impulses
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc428?
     *
     * @return $oJson: json object containing impulses data
     */
    public function getImpulses($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/impulses";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMapVariantData
     *
     * Return information about the givent map variant
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc429?
     *
     * @param $variantId: the id of the map variant wanted
     *
     * @return $oJson: json object containing datas of map variant
     */
    public function getMapVariantData($variantId, $lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/map-variant/".$variantId;
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMaps
     *
     * Return information about maps
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42a?
     *
     * @return $oJson: json object containing maps data
     */
    public function getMaps($start = null, $lang = null){
        $url = self::BASE_URL."metadata/".$this->title.( $this->title == 'h5' ? "/metadata/" : "/")."maps".
            ($this->title != 'h5' && !is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMedals
     *
     * Return information about medals
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42b?
     *
     * @return $oJson: json object containing medals data
     */
    public function getMedals($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/medals";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlaylists
     *
     * Return information about playlists
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42c?
     *
     * @return $oJson: json object containing playlists data
     */
    public function getPlaylists($start = null, $lang = null){

        $url = self::BASE_URL."metadata/".$this->title.( $this->title == 'h5' ? "/metadata/" : "/")."playlists".
            ($this->title != 'h5' && !is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getRequisition
     *
     * Return information about the given requisition
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42d?
     *
     * @param $requisitionId: ID of the requisition wanted
     *
     * @return $oJson: json object containing requisition data
     */
    public function getRequisition($requisitionId, $lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/requisitions/".$requisitionId;
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getRequisitionPack
     *
     * Return information about the given requisition pack
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42e?
     *
     * @param $packId: ID of the requisition pack wanted
     **
     * @return $oJson: json object containing requisition pack data
     */
    public function getRequisitionPack($packId, $lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/requisition-packs/".$packId;
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getSeasons
     *
     * Return information about seasons
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc42f?
     *
     * @return $oJson: json object containing seasons data
     */
    public function getSeasons($start = null, $lang = null){
        $url = self::BASE_URL."metadata/".$this->title.( $this->title == 'h5' ? "/metadata/" : "/")."seasons/".
            ($this->title != 'h5' && !is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getSkulls
     *
     * Return information about skulls
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc430?
     *
     * @return $oJson: json object containing skulls data
     */
    public function getSkulls($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/skulls";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getSpartanRanks
     *
     * Return information about spartan ranks
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc431?
     *
     * @return $oJson: json object containing spartan ranks data
     */
    public function getSpartanRanks($start = null, $lang = null){
        $url = self::BASE_URL."metadata/".$this->title.( $this->title == 'h5' ? "/metadata/" : "/")."spartan-ranks".
            ($this->title != 'h5' && !is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getTeamColors
     *
     * Return information about team colors
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc432?
     *
     * @return $oJson: json object containing team colors data
     */
    public function getTeamColors($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/team-colors";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getVehicles
     *
     * Return information about vehicles
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc433?
     *
     * @return $oJson: json object containing vehicles data
     */
    public function getVehicles($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/vehicles";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getWeapons
     *
     * Return information about weapons
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58ace18c21091812784ce8c5/operations/58ace18c2109180bdcacc434?
     *
     * @return $oJson: json object containing weapons data
     */
    public function getWeapons($lang = null){
        if($this->title != 'h5'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/metadata/weapons";
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCampaignLevels
     *
     * Return campaign levels for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getCampaignLevels($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/campaign-levels".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCampaignLogs
     *
     * Return campaign logs for Halo Wars 2
     *
     * @param null $start
     * @return bool|mixed|string
     */
    public function getCampaignLogs($start = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/campaign-logs".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCardKeywords
     *
     * Return cad keywords for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getCardKeywords($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/card-keywords".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCards
     *
     * Return cards for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getCards($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/cards".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getDifficulties
     *
     * Return difficulties for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getDifficulties($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/difficulties".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getGameObjectCategories
     *
     * Return game object categories for Halo Wars 2
     *
     * @param null $start
     * @return bool|mixed|string
     */
    public function getGameObjectCategories($start = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/game-object-categories".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getGameObjects
     *
     * Return game objects for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getGameObjects($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/game-objects".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getLeaderPowers
     *
     * Return leader powers for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getLeaderPowers($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/leader-powers".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getLeaders
     *
     * Return leaders for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getLeaders($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/leaders".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPacks
     *
     * Return packs for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getPacks($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/packs".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getTechs
     *
     * Return techs for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getTechs($start = null, $lang = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."metadata/".$this->title."/techs".(!is_null($start) ? "?startAt=".$start : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMetadata
     *
     * Return information about given metadata
     * Can be used instead of calling direct function
     *
     * @param $metadata name of metadata wanted
     * @param $id optional ID
     *
     * @return $oJson: json object containing weapons data
     */
    public function getMetadata($metadata, $id = null, $lang = null){
        $url = self::BASE_URL."metadata/".$this->title.($this->title == 'h5' ? "/metadata/" : "/") .$metadata.(!is_null($id) ? "/".$id : null);
        $response = $this->callAPI($url, $lang);

        return $this->decodeJson($response['body']);
    }

### End metadate part

### Stats part

    /**
     * @name getCompany
     *
     * Return information about the given company
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/596968ade2f7f7051870d29f?
     *
     * @param $companyId: ID of the company wanted
     *
     * @return $oJson: json object containing company data
     */
    public function getCompany($companyId){
        $url = self::BASE_URL."stats/".$this->title."/companies/".$companyId;
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getCompanyCommendations
     *
     * Return commendations of the given company
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/596968ade2f7f7051870d2a0?
     *
     * @param $companyId: ID of the company wanted
     *
     * @return $oJson: json object containing company commendations data
     */
    public function getCompanyCommendations($companyId){
        $url = self::BASE_URL."stats/".$this->title."/companies/".$companyId."/commendations";
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getLeaderboard
     *
     * Return leaderboard for givent season and playlist
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b35?
     *
     * @param $seasonId: ID of the season wanted
     * @param $playlistId: ID of the playlist wanted
     * @param $count: number of records to return (if not set, will return 200)
     *
     * @return $oJson: json object containing leaderboard data
     */
    public function getLeaderboard($seasonId, $playlistId, $count = null){
        $url = self::BASE_URL."stats/".$this->title."/player-leaderboards/csr/".$seasonId."/".$playlistId;

        if(!is_null($count)){
            $url.= "?count=".$count;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMatchEvents
     *
     * Return events of the given match
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b36?
     *
     * @param $matchId: ID of the match wanted
     *
     * @return $oJson: json object containing match events data
     */
    public function getMatchEvents($matchId){
        $url = self::BASE_URL."stats/".$this->title."/matches/".$matchId."/events";
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerCommendations
     *
     * Return player commendations
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/596968ade2f7f7051870d2a1?
     *
     * @param $playerId: ID of the player wanted
     *
     * @return $oJson: json object containing company data
     */
    public function getPlayerCommendations($playerId){
        $url = self::BASE_URL."stats/".$this->title."/players/".$playerId."/commendations";
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerMatches
     *
     * Return a list of all last matches of player
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3b?
     *
     * @param $params: array - default: null
     *          'modes': the id of the mode wanted - if not set, all modes are loaded - separate modes by coma - for h5
     *          'matchType': the id of the matchType wanted - if not set, all matchtypes are loaded - separate modes by coma - for hw2
     *          'start': the id of first element to return - if not set or set to 0, first match will be sent
     *          'count': the number of elements to return - if not set return 25
     *
     * @return $oJson: json object containing all matches datas
     */
    public function getPlayerMatches($params = array()){
        $url = self::BASE_URL."stats/".$this->title."/players/".$this->playerNames[0]."/matches";
        $i = 0;
        if(isset($params['modes']) && !is_null($params['modes'])){
            $url .= ($i == 0 ? "?" : "&")."modes=".$params['modes'];
            $i++;
        }
        if(isset($params['matchType']) && !is_null($params['matchType'])){
            $url .= ($i == 0 ? "?" : "&")."matchType=".$params['matchType'];
            $i++;
        }
        if(isset($params['start']) && !is_null($params['start'])){
            $url .= ($i == 0 ? "?" : "&")."start=".$params['start'];
            $i++;
        }
        if(isset($params['count']) && !is_null($params['count'])){
            $url .= ($i == 0 ? "?" : "&")."count=".$params['count'];
            $i++;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getMatchResult
     *
     * Return datas of given match
     *
     * Endpoint documentation:
     * Arena:           https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b37?
     * Campaign:        https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b38?
     * Custom:          https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b39?
     * Custom local:    https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/5a3d9a2e51059d1090806fe8?
     * Warzone:         https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3a?
     *
     * @param $matchId: id of the match wanted
     * @param $matchType: type of the wanted match (arena, campaign, custom, customlocal or warzone)
     *
     * @return $oJson: json object containing match datas
     */
    public function getMatchResult($matchId, $matchType){
        $url = self::BASE_URL."stats/".$this->title."/".$matchType."/matches/".$matchId;
        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getServiceRecords
     *
     * Return datas of given match
     *
     * Endpoint documentation:
     * Arena:           https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3c?
     * Campaign:        https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3d?
     * Custom:          https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3e?
     * Custom local:    https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/5a3d9a2e51059d1090806fe9?
     * Warzone:         https://developer.haloapi.com/docs/services/58acdf27e2f7f71ad0dad84b/operations/58acdf28e2f7f70db4854b3f?
     *
     *
     * @param $matchType: type of the wanted match (arena, campaign, custom, customelocal or warzone)
     *
     * @return $oJson: json object containing match datas
     */
    public function getServiceRecords($matchType){
        $url = self::BASE_URL."stats/".$this->title."/servicerecords/".$matchType;


        foreach($this->playerNames as $id => $val){
            $url .= ($id == 0 ? "?players=" : ",").$val;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerCampaignProgress
     *
     * Return player campaign progress for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getPlayerCampaignProgress($matchType){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."stats/".$this->title."/players/".$this->playerNames[0]."/campaign-progress";

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerPlaylistRatings
     *
     * Return player playlist ratings for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getPlayerPlaylistRatings($playlistId){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."stats/".$this->title."/playlist/".$playlistId."/rating?players=";

        $i=0;
        foreach($this->playerNames as $player){
            $url.=$player.($i<5 ? ",":null);
            $i++;
            if($i==6)
                break;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getSeasonStatsSummary
     *
     * Return season stats summary for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getSeasonStatsSummary($seasonId){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."stats/".$this->title."/players/".$this->playerNames[0]."/stats/seasons/".$seasonId;

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerStatsSummary
     *
     * Return player stats summary for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getPlayerStatsSummary($seasonId){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."stats/".$this->title."/players/".$this->playerNames[0]."/stats";

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerXPs
     *
     * Return player xp for Halo Wars 2
     *
     * @param null $start
     * @param null $lang
     * @return bool|mixed|string
     */
    public function getPlayerXPs(){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."stats/".$this->title."/xp?players=";

        $i=0;
        foreach($this->playerNames as $player){
            $url.=$player.($i<5 ? ",":null);
            $i++;
            if($i==6)
                break;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }


###

### UGC parts

    /**
     * @name getPlayerGameVariant
     *
     * Return metadata for given player and game variant
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acde2921091812784ce8c3/operations/58acde292109180bdcacc40c
     *
     * @param $playerId: ID of the player wanted
     * @param $variantId: ID of the variant wanted
     *
     * @return $oJson: json object containing variant data
     */
    public function getPlayerGameVariant($playerId, $variantId){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."ugc/".$this->title."/players/".$playerId."/gamevariants/".$variantId;

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerGameVariants
     *
     * Return a list of game variants created by given player
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acde2921091812784ce8c3/operations/58acde292109180bdcacc40d?
     *
     * @param $playerId: ID of the player wanted
     * @param $params: 'start': Starting index (0 if not set)
     *                  'count' : maximum number of items to return
     *                  'sort' : Field to should be used to sort data
     *                  'order' : Order for filter - desc if not set
     *
     * @return $oJson: json object containing all variants data
     */
    public function getPlayerGameVariants($playerId, $params = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."ugc/".$this->title."/players/".$playerId."/gamevariants";

        $i = 0;
        if(isset($params['start']) && !is_null($params['start'])){
            $url .= ($i == 0 ? "?" : "&")."start=".$params['start'];
            $i++;
        }
        if(isset($params['count']) && !is_null($params['count'])){
            $url .= ($i == 0 ? "?" : "&")."count=".$params['count'];
            $i++;
        }
        if(isset($params['sort']) && !is_null($params['sort'])){
            $url .= ($i == 0 ? "?" : "&")."sort=".$params['sort'];
            $i++;
        }
        if(isset($params['order']) && !is_null($params['order'])){
            $url .= ($i == 0 ? "?" : "&")."order=".$params['order'];
            $i++;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerMapVariant
     *
     * Return metadata for given player and map variant
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acde2921091812784ce8c3/operations/58acde292109180bdcacc40e?
     *
     * @param $playerId: ID of the player wanted
     * @param $variantId: ID of the variant wanted
     *
     * @return $oJson: json object containing variant data
     */
    public function getPlayerMapVariant($playerId, $variantId){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."ugc/".$this->title."/players/".$playerId."/mapvariants/".$variantId;

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }

    /**
     * @name getPlayerMapVariants
     *
     * Return a list of game variants created by given player
     *
     * Endpoint documentation:
     * https://developer.haloapi.com/docs/services/58acde2921091812784ce8c3/operations/58acde292109180bdcacc40f?
     *
     * @param $playerId: ID of the player wanted
     * @param $params: 'start': Starting index (0 if not set)
     *                  'count' : maximum number of items to return
     *                  'sort' : Field to should be used to sort data
     *                  'order' : Order for filter - desc if not set
     *
     * @return $oJson: json object containing all variants data
     */
    public function getPlayerMapVariants($playerId, $params = null){
        if($this->title != 'hw2'){
            trigger_error("Method ".__METHOD__ ." not implemented for the title " . $this->title, E_USER_WARNING);
            return false;
        }
        $url = self::BASE_URL."ugc/".$this->title."/players/".$playerId."/mapvariants";

        $i = 0;
        if(isset($params['start']) && !is_null($params['start'])){
            $url .= ($i == 0 ? "?" : "&")."start=".$params['start'];
            $i++;
        }
        if(isset($params['count']) && !is_null($params['count'])){
            $url .= ($i == 0 ? "?" : "&")."count=".$params['count'];
            $i++;
        }
        if(isset($params['sort']) && !is_null($params['sort'])){
            $url .= ($i == 0 ? "?" : "&")."sort=".$params['sort'];
            $i++;
        }
        if(isset($params['order']) && !is_null($params['order'])){
            $url .= ($i == 0 ? "?" : "&")."order=".$params['order'];
            $i++;
        }

        $response = $this->callAPI($url);

        return $this->decodeJson($response['body']);
    }


###
}
