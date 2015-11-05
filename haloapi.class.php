<?php

/**
 *
 * PHP-HaloAPI
 * v 0.1.0
 * Last mod: replacing HTTP_Request2 by curl
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

    const API_DEV_KEY = "xxxx"; // You API key - get one on https://developer.haloapi.com/
    const BASE_URL = "https://www.haloapi.com/"; // Base url for API, may change on day...

    private $sTitle         = ""; // Correspond to the game title - for now only Halo 5 (h5)
    private $aPlayerNames   = array(); // List of users (functions may use only the first user)


    /**
     * @name: __construct
     *
     * Initialize the class
     *
     * @param
     *      $aPlayerNames: an array containing list of players
     *      $sTitle: the title concerned by the API (for now, only h5 is valid) - default: h5
     */
    function __construct($aPlayerNames, $sTitle = "h5"){

        $this->aPlayerNames = $aPlayerNames;
        $this->sTitle = $sTitle;

    }

### Global functions
    /**
     * @name: callAPI
     *
     * Make curl request to the API
     *
     * @param
     *      $sUrl: url to use in API call
     *
     * @return
     *      $response: the API response
     */
    private function callAPI($sUrl){
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER         => true,
            CURLOPT_URL            => $sUrl,
            CURLOPT_USERAGENT      => 'PHP-HaloAPI',
            CURLOPT_HTTPHEADER     => array(
                'Ocp-Apim-Subscription-Key: '.self::API_DEV_KEY
            )
        ));

        if(!curl_exec($ch)){
            die('Error in API call: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        else{
            $resp = curl_exec($ch);
            // Then, after your curl_exec call:
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($resp, 0, $header_size);
            $body = substr($resp, $header_size);
        }
        curl_close($ch);

        return array('header' => $header, 'body' => $body);
    }

    /**
     * @name: getLocation
     *
     * Return Location information from curl header
     *
     * @param
     *     $sHeader: header returned by curl
     *
     * @return
     *      $headers: array containing all headers infos
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
     * @name: getEmblem
     *
     * Return the url of player's emblem img
     *
     * @param
     *      $sSize: size wanted - default: null
     *
     * @return
     *      $aHeader['location']: url of the img
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
     * @name: getSpartanImg
     *
     * Return the url of player's spartan img
     *
     * @param
     *      $sSize: size wanted - default: null
     *
     * @return
     *      $aHeader['location']: url of the img
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
    public function getAllMapsData(){

    }

    /**
     * @name: getMapVariantData
     *
     * Return information about the givent map variant
     *
     * @param
     *      $sVariantId: the id of the map variant wanted
     *
     * @return
     *      $oJson: json object containing datas of map variant
     */
    public function getMapVariantData($sVariantId){
        $sUrl = self::BASE_URL."/metadata/".$this->sTitle."/metadata/map-variant/".$sVariantId;
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }
### End metadate part

### Stats part

    /**
     * @name: getPlayerMatches
     *
     * Return a list of all last matches of player
     *
     * @param
     *      $aParams: array - default: null
     *          'modes': the id of the mode wanted - if not set, all modes are loaded - separate modes by coma
     *          'start': the id of first element to return - if not set or set to 0, first match will be sent
     *          'count': the number of elements to return - if not set return 25
     *
     * @return
     *      $oJson: json object containing all matches datas
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
     * @name: getPostGameCarnage
     *
     * Return datas of given match
     *
     * @param
     *      $sMatchId: id of the match wanted
     *      $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
     *
     * @return
     *      $oJson: json object containing match datas
     */
    public function getPostGameCarnage($sMatchId, $sMatchType){
        $sUrl = self::BASE_URL."/stats/".$this->sTitle."/".$sMatchType."/matches/".$sMatchId;
        $response = $this->callAPI($sUrl);

        return json_decode($response['body']);
    }

    /**
     * @name: getServiceRecords
     *
     * Return datas of given match
     *
     * @param
     *      $sMatchType: type of the wanted match (arena, campaign, custom or warzone)
     *
     * @return
     *      $oJson: json object containing match datas
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