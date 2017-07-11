<?php
/**
 * Simple Zoho CRM inserter.
 *
 * MIT licensed. Originally written by Pete Sevander and Mikko Ohtamaa in 2011
 * Enhanced by Jeremy Nagel to make it a bit easier to use and update it with latest changes to the API
 *
 */

class ZohoException extends Exception { }


/**
 *
 *
 *
 * Class Zoho
 */
class Zoho {


    protected $domain;




    /**
     * Zoho constructor.
     * @param $authtoken
     * @param array $extra_auth_params
     * @param string $auth_url
     * @param string $domain    The Domain for the Zoho API (eu or com)
     */
    public function __construct($authtoken, $extra_auth_params = array(), $auth_url="https://accounts.zoho.com/login",$domain = 'https://crm.zoho.com') {
        $this->authtoken = $authtoken;

        $this->ticket = null;

        $this->domain = $domain;
    }

    /**
     * Retrieves a single record from a specified Zoho CRM module based on a record ID
     *
     *
     * @param  $module  array The module from which the record will be retrieved
     * @param  $record_id  int The ID of the record that will be retrieved
     * @return array
     */
    public function get_module_data_by_id($module, $record_id) {

        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'scope'=>'crmapi',
            'selectColumns' =>'All',
            'id' => $record_id
        );

        $q = http_build_query($post);

        $response = $this->openUrl("{$this->domain}/crm/private/json/$module/getRecordById",$q);

        return $this->json_to_array(json_decode($response), $module);

    }

    /**
     * Retrieves a single record from a specified Zoho CRM module based on some search criteria
     * @param  $module  array The module from which the record will be retrieved
     * @param  $search_criteria int The criteria used to search for the record
     *
     *
     * @return array
     */
    public function get_record_by_searching($module, $search_criteria) {
        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'scope'=>'crmapi',
            'selectColumns' =>'All',
            'criteria' => $search_criteria
        );

        $q = http_build_query($post);



        $response = $this->openUrl("{$this->domain}/crm/private/json/$module/searchRecords",$q);

        return $this->json_to_array(json_decode($response), $module);

    }




    /**
     * @param $response_data string|object|array
     * @param $module
     * @param $unique_as_multiple   boolean     if True, the value returned will be in a multidimentional aray even if there is only 1 record
     * @return array
     */
    public function json_to_array($response_data, $module,$unique_as_multiple = false){


        $array_contents = array();


        if(is_object($response_data)){
            $response_data = json_decode(json_encode($response_data),true);
        }else if(is_string($response_data)){
            $response_data = json_decode($response_data,true);
        }


        if(isset($response_data['response']['result'][$module]['row'][0])){
            $entries = $response_data['response']['result'][$module]['row'];


            foreach($entries as $entry){
                $array_contents[] = $this->get_entry_value($entry);
            }


            return $array_contents;

        }else{

            if(!isset($response_data['response']['result'])){
                return array();
            }

            $data = $this->get_entry_value($response_data['response']['result'][$module]['row']);

            return $unique_as_multiple ? array($data) : $data;

        }

    }


    /**
     * @param $entry
     * @return array
     */
    protected function get_entry_value($entry)
    {
        $array_contents = array();


        foreach($entry['FL'] as $key => $record){
            $array_contents[$record['val']] = $record['content'];
        }
        return $array_contents;


    }




    /**
     *  Updates a specified record in Zoho CRM
     *  @param  $module  array The module in which the record resides
     *  @param  $record_ID  int The ID of the record to be updated
     *  @param  $update_data  string The new data for the record
     *  @param array $extra_post_parameters
     *
     * https://crm.zoho.com/crm/private/xml/Module/updateRecords?newFormat=1&apikey=APIkey&ticket=Ticket
     *
     * @return bool
     * @throws ZohoException
     */
    public function update_record($module, $record_ID, $update_data, $extra_post_parameters=array()) {
        $xmldata = $this->XMLfy($update_data, $module);
        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'xmlData' => $xmldata,
            'duplicateCheck' => 2,
            'wfTrigger' => 'true',
            'id' => $record_ID
        );

        $post = array_merge($post, $extra_post_parameters);

        $q = http_build_query($post);


        $response = $this->openUrl("{$this->domain}/crm/private/xml/$module/updateRecords", $q);

        $this->check_successful_xml($response);

        return true;

    }



     /**
     *  Updates a specified record in Zoho CRM
      *
     *  @param $module                  array       The module in which the record resides
     *  @param $record_ID               int         The ID of the record to be updated
     *  @param $relatedModule           string      The Related Module you want to update
     *  @param $update_data             string      The new data for the record
     *  @param $extra_post_parameters   array
     *
     * https://crm.zoho.com/crm/private/xml/Module/updateRecords?newFormat=1&apikey=APIkey&ticket=Ticket
     *
     * @return string
     * @throws ZohoException
     */
    public function update_related_records($module, $record_ID,$relatedModule, $update_data, $extra_post_parameters=array()) {
        $xmldata = $this->XMLfy($update_data, $relatedModule);



        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'xmlData' => $xmldata,
            'duplicateCheck' => 2,
            'wfTrigger' => 'true',
            'scope'=>'crmapi',
            'id' => $record_ID,
            'relatedModule' => $relatedModule
        );

        $post = array_merge($post, $extra_post_parameters);

        $q = http_build_query($post);

        $response = $this->openUrl("{$this->domain}/crm/private/xml/$module/updateRelatedRecords", $q);

        $this->check_successful_xml($response);

        return $response;

    }


        /**
     * @param $module   string
     * @param $extra_post_parameters    array
     * @param $mine    boolean      If true, It will retrieve only the owner's record
     *
     * @return mixed|null
     * @throws ZohoException
     */
    public function get_records($module,$extra_post_parameters = array(),$mine = false)
    {

        $post = array(
            'newFormat' => 2,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'scope'=>'crmapi',
            'selectColumns' => 'First Name,Last Name,Company,Email,Website'
        );

        $post = array_merge($post, $extra_post_parameters);


        $q = http_build_query($post);


        $key = $mine ? 'getMyRecords' : 'getRecords';

        $response = $this->openUrl("{$this->domain}/crm/private/json/$module/$key", $q);


        return $this->json_to_array($response, $module,true);

    }





    /**
     *  Adds a new record in Zoho CRM
     *  @param  $module  array The module in which the record will reside
     *  @param  $update_data  array The new data for the record
     *  @param  $extra_post_parameters  array Some POST Parameters
     *
     * @return mixed|null
     * @throws ZohoException
     *
     * https://crm.zoho.com/crm/private/xml/Module/updateRecords?newFormat=1&apikey=APIkey&ticket=Ticket
     **/
    public function insert_record($module, $update_data, $extra_post_parameters=array()) {
        $xmldata = $this->XMLfy($update_data, $module);
        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'xmlData' => $xmldata,
            'duplicateCheck' => 2,
            'wfTrigger' => 'true'
        );

        $post = array_merge($post, $extra_post_parameters);

        $q = http_build_query($post);


        $response = $this->openUrl("{$this->domain}/crm/private/xml/$module/insertRecords", $q);

        $this->check_successful_xml($response);

        return $response;

    }









    /**
     * https://crm.zoho.com/crm/private/xml/Leads/convertLead?newFormat=1&apikey=APIkey&ticket=Ticket
     *
     *
     * @param $lead_ID
     * @param $potential_data
     * @param array $extra_post_parameters
     * @return mixed
     * @throws ZohoException
     */
    public function convert_lead($lead_ID, $potential_data, $extra_post_parameters=array()) {
        $this->ensure_opened();
        $xmldata = $this->XMLfy($potential_data, "Potentials");
        $post = array(
            'newFormat' => 1,
            'authtoken' => $this->authtoken,
            'version' => 2,
            'xmlData' => $xmldata,
            'duplicateCheck' => 2,
            'wfTrigger' => 'true',
            'leadId' => $lead_ID
        );

        $post = array_merge($post, $extra_post_parameters);

        $q = http_build_query($post);

        $response = $this->openUrl("{$this->domain}/crm/private/xml/Leads/convertLead", $q);

        $this->check_successful_xml($response);

        return $response;

    }

    /**
     * Uses curl to open a URL
     *
     * @param $url
     * @param null $data
     * @return mixed|null
     */
    public function openUrl($url, $data=null) {
        $ch = curl_init();
        $timeout = 5;

        if($data) {
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch,CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        }

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    /**
     *
     * Check if a XML is correct
     *
     * @param $response
     * @return bool
     * @throws ZohoException
     */
    public function check_successful_xml($response) {
        $html = new DOMDocument();
        $html->loadXML($response);

        /** @var DOMDocument $err */
        if ($err = $html->getElementsByTagName('error')->item(0)) {
            throw new ZohoException($err->getElementsByTagName('message')->item(0)->nodeValue);
        }

        return true;
    }




    /**
     * @param $arr
     * @param $openingBracket
     * @return string
     */
    public function XMLfy ($arr, $openingBracket) {
        $xml = "<$openingBracket>";
        $no = 1;
        foreach ($arr as $a) {
            $xml .= "<row no=\"$no\">";
            foreach ($a as $key => $val) {
                $xml .= "<FL val=\"$key\"><![CDATA[" . trim($val) . "]]></FL>";
            }
            $xml .= "</row>";
            $no += 1;
        }
        $xml .= "</$openingBracket>";

        return $xml;
    }

}
