<?php

/**
 *
 * PHP-HaloAPI
 * v 1.0.1-beta
 *
 * This class has for purpose to simplify the work of PHP developers who wants to use the official (beta) Halo 5 API.
 *
 * Author: Gaspard Rosay - @BananasSplitter
 * Date: 04.11.15
 * WTPFL Licence
 *
 */

class haloapi
{

    const BASE_URL          = "https://www.haloapi.com/"; // Base url for API, may change on day...

    private $sApiKey        = ""; // Will contain the API key
    private $sTitle         = ""; // Correspond to the game title - for now only Halo 5 (h5)
    private $aPlayerNames   = array(); // List of users (functions may use only the first user)

    public static $queryLimit = 10; // The number of queries that you are allowed to perform within the alloted time window.
    public static $queryWindowSecs = 10; // The time window on which queries are bound, in seconds.

    private static $queryCount = 0;
    private static $queryWindowStartTime;


    /**
     * @name __construct
     *
     * Initialize the class
     *
     * @param $aPlayerNames: an array containing list of players
     * @param $sTitle: the title concerned by the API (for now, only h5 is valid) - default: h5
     */
    function __construct($sApiKey, $aPlayerNames, $sTitle = "h5"){

        $this->sApiKey = $sApiKey;
        $this->aPlayerNames = $aPlayerNames;
        $this->sTitle = $sTitle;
    }

### Global functions
    /**
     * @name callAPI
     *
     * Make curl request to the API
     *
     * @param $sUrl: url to use in API call
     *
     * @return $response: the API response
     */
    private function callAPI($sUrl){
        self::throttle();

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER         => true,
            CURLOPT_URL            => $sUrl,
            CURLOPT_USERAGENT      => 'PHP-HaloAPI',
            CURLOPT_HTTPHEADER     => array(
                'Ocp-Apim-Subscription-Key: '.$this->sApiKey
            )
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

        return array('header' => $header, 'body' => $body);
    }

    private static function throttle() {
      self::$queryCount++;

      if(self::$queryWindowStartTime === null) {
        self::$queryWindowStartTime = new DateTime();
      }

      if(self::$queryCount > self::$queryLimit) {

        $now = new DateTime();
        $diffSec = $now->getTimestamp() - self::$queryWindowStartTime->getTimestamp();
        // If we've exceeded the query count, and we still are within the query window, then wait.
        if($diffSec < self::$queryWindowSecs) {
          sleep(self::$queryWindowSecs - $diffSec + 1);
        }

        // Then, once we've waited, if necessary, we'll assume that we're beyond the query window, so we'll start our throttling over.
        self::$queryWindowStartTime = new DateTime();
        self::$queryCount = 1;
      }
    }

    /**
     * @name getLocation
     *
     * Return Location information from curl header
     *
     * @param $sHeader: header returned by curl
     *
     * @return $headers: array containing all headers infos
     */
    private function getLocation($sHeader){

        $headers = array();

        $header_text = substr($sHeader, 0, strpos($sHeader, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }
###

### Profile part
    /**
     * @name getEmblem
     *
     * Return the url of player's emblem img
     *
     * @param $sSize: size wanted - default: null
     *
     * @return $aHeader['location']: url of the img
     */
    public function getEmblem($sSize = null){
        $sUrl = self::BASE_URL."profile/".$this->sTitle."/profiles/".$this->aPlayerNames[0]."/emblem";

        if(!is_null($sSize))
            $sUrl.= "?size=".$sSize;

        $response = $this->callAPI($sUrl);

        $header = $this->getLocation($response['header']);

        return $header['Location'];
    }

    /**
     * @name getSpartanImg
     *
     * Return the url of player's spartan img
     *
     * @param $sSize: size wanted - default: null
     *
     * @return $aHeader['location']: url of the img
     */
    public function getSpartanImg($sSize = null){
        $sUrl = self::BASE_URL."profile/".$this->sTitle."/profiles/".$this->aPlayerNames[0]."/spartan";
        if(!is_null($sSize))
            $sUrl.= "?size=".$sSize;

        $response = $this->callAPI($sUrl);
        $header = $this->getLocation($response['header']);

        return $header['Location'];
    }
### End of profile part

### Metadata part

    /**
     * @name getCampaignMissions
     *
     * Return information of all campaign missions
     **
     * @return $oJson: json object containing campaign informations
     */
    public function getCampaignMissions(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/campaign-missions";
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getCommendations
     *
     * Return information about all commendations
     **
     * @return $oJson: json object containing commendations data
     */
    public function getCommendations(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/commendations";
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getCSRDesignations
     *
     * Return information about all CSR Designations
     **
     * @return $oJson: json object containing csr designations data
     */
    public function getCSRDesignations(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/csr-designations";
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getEnemies
     *
     * Return information about all enemies (IA)
     **
     * @return $oJson: json object containing enemies data
     */
    public function getEnemies(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/enemies";
        $response = $this->callAPI($sUrl);
        $response['body'] = str_replace('&quot;', '"', $response['body']); // !! Important for decode to work here
        return json_decode($response['body']);
    }

    /**
     * @name getFlexibleStats
     *
     * Return information about flexible stats
     **
     * @return $oJson: json object containing flexible stats data
     */
    public function getFlexibleStats(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/flexible-stats";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getGameBaseVariants
     *
     * Return information about game base variants
     **
     * @return $oJson: json object containing game base variants data
     */
    public function getGameBaseVariants(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/game-base-variants";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getGameVariantData
     *
     * Return information about the given game variant
     *
     * @param $sPackId: ID of the game variant wanted
     *
     * @return $oJson: json object containing game variant data
     */
    public function getGameVariantData($sVariantId){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/game-variants/".$sVariantId;
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getImpulses
     *
     * Return information about impulses
     **
     * @return $oJson: json object containing impulses data
     */
    public function getImpulses(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/impulses";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getMapVariantData
     *
     * Return information about the givent map variant
     *
     * @param $sVariantId: the id of the map variant wanted
     *
     * @return $oJson: json object containing datas of map variant
     */
    public function getMapVariantData($sVariantId){
        $sUrl = self::BASE_URL."/metadata/".$this->sTitle."/metadata/map-variant/".$sVariantId;
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getMaps
     *
     * Return information about maps
     **
     * @return $oJson: json object containing maps data
     */
    public function getMaps(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/maps";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getMedals
     *
     * Return information about medals
     **
     * @return $oJson: json object containing medals data
     */
    public function getMedals(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/medals";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getPlaylists
     *
     * Return information about playlists
     **
     * @return $oJson: json object containing playlists data
     */
    public function getPlaylists(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/playlists";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getRequisitionPack
     *
     * Return information about the given requisition pack
     *
     * @param $sPackId: ID of the requisition pack wanted
     **
     * @return $oJson: json object containing requisition pack data
     */
    public function getRequisitionPack($sPackId){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/requisition-packs/".$sPackId;
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getRequisitionPack
     *
     * Return information about the given requisition
     *
     * @param $sRequisitionId: ID of the requisition wanted
     *
     * @return $oJson: json object containing requisition data
     */
    public function getRequisition($sRequisitionId){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/requisitions/".$sRequisitionId;
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getSkulls
     *
     * Return information about skulls
     **
     * @return $oJson: json object containing skulls data
     */
    public function getSkulls(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/playlists";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getSpartanRanks
     *
     * Return information about spartan ranks
     **
     * @return $oJson: json object containing spartan ranks data
     */
    public function getSpartanRanks(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/spartan-ranks";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getTeamColors
     *
     * Return information about team colors
     **
     * @return $oJson: json object containing team colors data
     */
    public function getTeamColors(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/team-colors";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getVehicles
     *
     * Return information about vehicles
     **
     * @return $oJson: json object containing vehicles data
     */
    public function getVehicles(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/vehicles";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getWeapons
     *
     * Return information about weapons
     **
     * @return $oJson: json object containing weapons data
     */
    public function getWeapons(){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/weapons";
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

    /**
     * @name getMetadata
     *
     * Return information about given metadata
     * Can be used instead of calling direct function
     *
     * @param $sMetadata name of metadata wanted
     * @param $sId optional ID
     *
     * @return $oJson: json object containing weapons data
     */
    public function getMetadata($sMetadata, $sId = null){
        $sUrl = self::BASE_URL."metadata/".$this->sTitle."/metadata/".$sMetadata.(!is_null($sId) ? "/".$sId : null);
        $response = $this->callAPI($sUrl);
        return json_decode($response['body']);
    }

### End metadate part

### Stats part

    /**
     * @name getPlayerMatches
     *
     * Return a list of all last matches of player
     *
     * @param $aParams: array - default: null
     *          'modes': the id of the mode wanted - if not set, all modes are loaded - separate modes by coma
     *          'start': the id of first element to return - if not set or set to 0, first match will be sent
     *          'count': the number of elements to return - if not set return 25
     *
     * @return $oJson: json object containing all matches datas
     */
    public function getPlayerMatches($aParams = array()){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/players/".$this->aPlayerNames[0]."/matches";
        $i = 0;
        if(isset($aParams['modes']) && !is_null($aParams['modes'])){
            $sUrl .= ($i == 0 ? "?" : "&")."modes=".$aParams['modes'];
            $i++;
        }
        if(isset($aParams['start']) && !is_null($aParams['start'])){
            $sUrl .= ($i == 0 ? "?" : "&")."start=".$aParams['start'];
            $i++;
        }
        if(isset($aParams['count']) && !is_null($aParams['count'])){
            $sUrl .= ($i == 0 ? "?" : "&")."count=".$aParams['count'];
            $i++;
        }

        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getPostGameCarnage
     *
     * Return datas of given match
     *
     * @param $sMatchId: id of the match wanted
     * @param $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
     *
     * @return $oJson: json object containing match datas
     */
    public function getPostGameCarnage($sMatchId, $sMatchType){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/".$sMatchType."/matches/".$sMatchId;
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name getServiceRecords
     *
     * Return datas of given match
     *
     * @param $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
     *
     * @return $oJson: json object containing match datas
     */
    public function getServiceRecords($sMatchType){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/servicerecords/".$sMatchType;

        foreach($this->aPlayerNames as $id => $val){
            $sUrl .= ($id == 0 ? "?players=" : ",").$val;
        }

        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }



###
}
