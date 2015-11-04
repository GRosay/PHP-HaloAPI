<?php

/**
 *
 * PHP-HaloAPI
 * v 0.0.1
 *
 * This class has for purpose to simplify the work of PHP developers who wants to use the official (beta) Halo 5 API.
 *
 * Author: Gaspard Rosay
 * Date: 04.11.15
 * WPFL Licence
 *
 */

class haloapi
{

    const API_DEV_KEY = "xxxx"; // You API key - get one on https://developer.haloapi.com/
    const BASE_URL = "https://www.haloapi.com/"; // Base url for API, may change on day...

    private $sTitle         = ""; // Correspond to the game title - for now only Halo 5 (h5)
    private $sMethod        = ""; // Method to use (POST or GET, by default GET)
    private $aParameters    = array(); // Parameters of the query (get method)
    private $aHeaders       = array();
    private $aPlayerNames   = array(); // List of users (functions may use only the first user)


    /*
     * __construct
     *
     * Initialize the class
     *
     * Parameters:
     *      $aPlayerNames: an array containing list of players
     *      $sTitle: the title concerned by the API (for now, only h5 is valid) - default: h5
     *      $sMethod: Method to use for parameters in API calls - default: HTTP_Request2::METHOD_GET
     */
    function __construct($aPlayerNames, $sTitle = "h5", $sMethod = HTTP_Request2::METHOD_GET){

        $this->aHeaders = array(
            'Ocp-Apim-Subscription-Key' => self::API_DEV_KEY
        );

        $this->aPlayerNames = $aPlayerNames;
        $this->sTitle = $sTitle;
        $this->sMethod = $sMethod;

    }

### Global functions
    /*
     * fCallAPI - private
     *
     * Make Http_Request calls to API
     *
     * Parameters:
     *      $sUrl: url to use in API call
     *
     * Return:
     *      $response: the API response
     */
    private function fCallAPI($sUrl){
        $request = new Http_Request2($sUrl);
        $url = $request->getUrl();

        $request->setHeader($this->aHeaders);

        $url->setQueryVariables($this->aParameters);

        $request->setMethod($this->sMethod);

        try{
            $response = $request->send();
        }
        catch(HttpException $ex){
            die("API call error: ".$ex);
        }
        return $response;
    }

    /*
     * fGetBody - private
     *
     * Return the body part of the response
     *
     * Parameters:
     *      $response: the API response
     *
     * Return:
     *      $sJson: the json string de-encoded contained in the $response
     */
    private function fGetBody($response){
        $sJson = $response->getBody();
        return json_decode($sJson);
    }

    /*
     * fGetHeader - private
     *
     * Return the header part of the response
     *
     * Parameters:
     *      $response: the API response
     *
     * Return:
     *      $sJson: the header contained in the $response
     */
    private function fGetHeader($response){
        $aHeader = $response->getHeader();
        return $aHeader;
    }
###

### Profile part
    /*
     * fGetEmblemImg - public
     *
     * Return the url of player's emblem img
     *
     * Parameters:
     *      $sSize: size wanted - default: null
     *
     * Return:
     *      $aHeader['location']: url of the img
     */
    public function fGetEmblemImg($sSize = null){
        $sUrl = self::BASE_URL."profile/".$this->sTitle."/profiles/".$this->aPlayerNames[0]."/emblem";
        $this->aParameters = array();

        if(!is_null($sSize))
            $this->aParameters['size'] = $sSize;

        $response = $this->fCallAPI($sUrl);
        $aHeader = $this->fGetHeader($response);

        return $aHeader['location'];
    }

    /*
     * fGetSpartanImg - public
     *
     * Return the url of player's spartan img
     *
     * Parameters:
     *      $sSize: size wanted - default: null
     *
     * Return:
     *      $aHeader['location']: url of the img
     */
    public function fGetSpartanImg($sSize = null){
        $sUrl = self::BASE_URL."profile/".$this->sTitle."/profiles/".$this->aPlayerNames[0]."/spartan";
        $this->aParameters = array();
        if(!is_null($sSize))
            $this->aParameters['size'] = $sSize;

        $response = $this->fCallAPI($sUrl);
        $aHeader = $this->fGetHeader($response);

        return $aHeader['location'];
    }
### End of profile part

### Metadata part
    public function fGetAllMapsData(){

    }

    /*
     * fGetMapVariantData - public
     *
     * Return information about the givent map variant
     *
     * Parameters:
     *      $sVariantId: the id of the map variant wanted
     *
     * Return:
     *      $oJson: json object containing datas of map variant
     */
    public function fGetMapVariantData($sVariantId){
        $sUrl = self::BASE_URL."/metadata/".$this->sTitle."/metadata/map-variant/".$sVariantId;
        $this->aParameters = array();
        $response = $this->fCallAPI($sUrl);
        $oJson = $this->fGetBody($response);

        return $oJson;
    }
### End metadate part

### Stats part

    /*
     * fGetPlayerMatches - public
     *
     * Return a list of all last matches of player
     *
     * Parameters:
     *      $aParams: array - default: null
     *          'modes': the id of the mode wanted - if not set, all modes are loaded - separate modes by coma
     *          'start': the id of first element to return - if not set or set to 0, first match will be sent
     *          'count': the number of elements to return - if not set return 25
     *
     * Return:
     *      $oJson: json object containing all matches datas
     */
    public function fGetPlayerMatches($aParams = array()){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/players/".$this->aPlayerNames[0]."/matches";
        $this->aParameters = array();
        if(isset($aParams['modes']) && !is_null($aParams['modes']))
            $this->aParameters['modes'] = $aParams['modes'];
        if(isset($aParams['start']) && !is_null($aParams['start']))
            $this->aParameters['start'] = $aParams['start'];
        if(isset($aParams['count']) && !is_null($aParams['count']))
            $this->aParameters['count'] = $aParams['count'];

        $response = $this->fCallAPI($sUrl);
        $oJson = $this->fGetBody($response);

        return $oJson;
    }

    /*
    * fGetPostGameCarnage - public
    *
    * Return datas of given match
    *
    * Parameters:
    *      $sMatchId: id of the match wanted
    *      $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
    *
    * Return:
    *      $oJson: json object containing match datas
    */
    public function fGetPostGameCarnage($sMatchId, $sMatchType){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/".$sMatchType."/matches/".$sMatchId;
        $this->aParameters = array();
        $response = $this->fCallAPI($sUrl);
        $oJson = $this->fGetBody($response);

        return $oJson;
    }

    /*
    * fGetPostGameCarnage - public
    *
    * Return datas of given match
    *
    * Parameters:
    *      $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
    *
    * Return:
    *      $oJson: json object containing match datas
    */
    public function fGetServiceRecords($sMatchType){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/servicerecords/".$sMatchType;
        $this->aParameters = array();
        $sPlayList = "";
        foreach($this->aPlayerNames as $id => $val){
            $sPlayList .= ($id != 0 ? "," : null).$val;
        }

        $this->aParameters['players'] = $sPlayList;

        $response = $this->fCallAPI($sUrl);
        $oJson = $this->fGetBody($response);

        return $oJson;
    }



###
}