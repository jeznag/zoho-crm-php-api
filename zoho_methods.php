<?php
/**
 * Simple Zoho CRM inserter.
 *
 * MIT licensed. Originally written by Pete Sevander and Mikko Ohtamaa in 2011
 * Enhanced by Jeremy Nagel to make it a bit easier to use and update it with latest changes to the API
 * Version 2 enhancements by Jon Hanson.
 */

class ZohoException extends Exception
{
}

/**
 *
 * Class Zoho
 */
class Zoho
{
	protected $authtoken;
	protected $debug_log;
	protected $domain;
	protected $timeout;

	/**
	 * Zoho constructor.
	 * @param $auth_token   string  Zoho authentication token
	 * @param $debug_log    string  Path and filename of debug log file.
	 * @param $domain       string  The Domain for the Zoho API (eu or com)
	 */
	public function __construct($auth_token, $debug_log=null, $domain = 'https://crm.zoho.com', $timeout=5)
	{
		$this->authtoken = $auth_token;
		$this->debug_log = $debug_log;
		$this->domain = $domain;
		$this->timeout = $timeout;
	}


	/**
	 * Retrieves a single record from a specified Zoho CRM module based on a record ID
	 *
	 * @param $module       string  The module from which the record will be retrieved
	 * @param $record_id    int     The ID of the record that will be retrieved
	 *
	 * @return array
	 * @throws ZohoException
	 */
	public function get_record_by_id($module, $record_id)
	{
		$post = array(
			'newFormat' => 1,
			'authtoken' => $this->authtoken,
			'version' => 2,
			'scope' => 'crmapi',
			'selectColumns' => 'All',
			'id' => $record_id
		);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/getRecordById", $q);
		return $this->json_to_array(json_decode($response), $module);
	}


	/**
	 * Retrieves a single record from a specified Zoho CRM module based on some search criteria
	 *
	 * @param $module                   string  The module from which the record will be retrieved
	 * @param $search_criteria          int     The criteria used to search for the record
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 *
	 * @return array
	 * @throws ZohoException
	 */
	public function get_record_by_searching($module, $search_criteria, $extra_post_parameters = array())
	{
		$post = array(
			'newFormat' => 1,
			'authtoken' => $this->authtoken,
			'version' => 2,
			'scope' => 'crmapi',
			'selectColumns' => 'All',
			'criteria' => $search_criteria
		);
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/searchRecords", $q);
		return $this->json_to_array(json_decode($response), $module);
	}


	/**
	 * Retrieves multiple records from a specified Zoho CRM module based on some search criteria
	 *
	 * @param $module                   string  The module from which the record will be retrieved
	 * @param $search_criteria          int     The criteria used to search for the record
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 * @param $start_index              int     Index of first record to be retrieved.
	 * @param $end_index                int     Index of last record to be retrieved.
	 *
	 * @return array
	 * @throws ZohoException
	 */
	public function get_records_by_searching($module, $search_criteria, $extra_post_parameters = array(), $start_index, $end_index)
	{
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'criteria' => $search_criteria,
			'selectColumns' => 'All',
			'fromIndex' => $start_index,
			'toIndex' => $end_index,
			'newFormat' => 2
		);
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/searchRecords", $q);
		return $this->json_to_array(json_decode($response), $module);
	}


	/**
	 *  Updates a single record in Zoho CRM
	 * @param $module                   string  The module in which the record resides
	 * @param $record_ID                int     The ID of the record to be updated
	 * @param $update_data              array   The new data for the record
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 * @param $allow_duplicates         boolean Determines whether a duplicate record is allowed.
	 * @param $workflow_trigger         boolean Determines whether the update triggers any relavant workflows.
	 *
	 * https://crm.zoho.com/crm/private/xml/Module/updateRecords?authtoken=Auth_Token&scope=crmapi&id=Record_ID&xmlData=XML_Data
	 *
	 * @return bool
	 * @throws ZohoException
	 */
	public function update_record($module, $record_ID, $update_data, $extra_post_parameters = array(), $allow_duplicates = false, $workflow_trigger = false)
	{
		$xmldata = $this->XMLfy(array($update_data), $module);
		if ($workflow_trigger) {
			$workflow_trigger='true';
		} else {
			$workflow_trigger='false';
		}
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'id' => $record_ID,
			'xmlData' => $xmldata,
			'wfTrigger' => $workflow_trigger,
			'newFormat' => 1,
			'version' => 2
		);
		if (!$allow_duplicates) $post['duplicateCheck'] = 2;
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/xml/$module/updateRecords", $q);
		// Convert XML to json:
		$response = json_encode(simplexml_load_string($response));
		return $this->json_to_array($response, $module);
	}


	/**
	 *  Updates multiple records in Zoho CRM
	 * @param $module                   string          The module in which the record resides
	 * @param $update_data              array of arrays The new data for the record.  Each record must contain the Id field.
	 * @param $extra_post_parameters    array           Used to add or override post parameters.
	 * @param $allow_duplicates         boolean         Determines whether a duplicate record is allowed.
	 * @param $workflow_trigger         boolean         Determines whether the update triggers any relavant workflows.
	 *
	 * https://crm.zoho.com/crm/private/xml/Module/updateRecords?authtoken=Auth_Token&scope=crmapi&version=4&xmlData=XML Data
	 *
	 * @return string   XML response
	 * @throws ZohoException
	 * Note: You can update a maximum of 100 records in a single API call.
	 */
	public function update_records($module, $update_data, $extra_post_parameters = array(), $allow_duplicates = false, $workflow_trigger = false)
	{
		if (count($update_data)>100) throw new ZohoException('Too many records, Only 100 records may be updated in a single API call.');
		$xmldata = $this->XMLfy($update_data, $module);
		if ($workflow_trigger) {
			$workflow_trigger='true';
		} else {
			$workflow_trigger='false';
		}
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'xmlData' => $xmldata,
			'wfTrigger' => $workflow_trigger,
			'newFormat' => 2,
			'version' => 4
		);
		if (!$allow_duplicates) $post['duplicateCheck'] = 2;
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response=$this->openUrl("{$this->domain}/crm/private/xml/$module/updateRecords", $q);
		// Convert XML to json:
		$response = json_encode(simplexml_load_string($response));
		return $this->json_to_array($response, $module);

	}


	/**
	 *  Updates a specified record in Zoho CRM
	 *
	 * @param $module                  string           The module in which the record resides
	 * @param $record_ID               int              The ID of the record to be updated
	 * @param $relatedModule           string           The Related Module you want to update
	 * @param $update_data             array of arrays  The new data for the record
	 * @param $extra_post_parameters   array            Used to add or override post parameters.
	 * @param $allow_duplicates        boolean          Determines whether a duplicate record is allowed.
	 *
	 * https://crm.zoho.com/crm/private/json/Module/updateRelatedRecords?authtoken=Auth_Token&scope=crmapi&relatedModule=Module&xmlData=XML_Data&id=Record_ID
	 *
	 * @return string
	 * @throws ZohoException
	 */
	public function update_related_records($module, $record_ID, $relatedModule, $update_data, $extra_post_parameters = array(), $allow_duplicates = false)
	{
		$xmldata = $this->XMLfy($update_data, $relatedModule);
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'relatedModule' => $relatedModule,
			'id' => $record_ID,
			'xmlData' => $xmldata
			//2018-3-9 removed the following arguments.  Not shown in Zoho document, verify that they exist:
			//'newFormat' => 1,
			//'version' => 2,
			//'wfTrigger' => 'true'
		);
		if (!$allow_duplicates) $post['duplicateCheck'] = 2;
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/updateRelatedRecords", $q);
		return $this->json_to_array($response, $module);
	}


	/**
	 * @param $module                   string  The module from which the record will be retrieved
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 * @param $mine                     boolean If true, It will retrieve only the owner's record
	 * @param $start_index              int     Index of first record to be retrieved.
	 * @param $end_index                int     Index of last record to be retrieved.
	 * @param $sort_column              string  (optional) Column used to sort the records.
	 * @param $sort_ascending           boolean (optional) True to sort ascending, False for descending.
	 *
	 * @return mixed|null
	 * @throws ZohoException
	 */
	public function get_records($module, $start_index, $end_index, $extra_post_parameters = array(), $mine = false, $sort_column = null, $sort_ascending=true)
	{
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'selectColumns' => 'All',
			'fromIndex' => $start_index,
			'toIndex' => $end_index,
			'newFormat' => 2,
			'version' => 2
		);
		if (null!=$sort_column) {
			$post['sortColumnString']=$sort_column;
			if ($sort_ascending) {
				$post['sortOrderString']='asc';
			}
			else{
				$post['sortOrderString']='desc';
			}
		}

		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$key = $mine ? 'getMyRecords' : 'getRecords';
		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/$key", $q);
		return $this->json_to_array($response, $module, true);
	}


	/**
	 *  Adds a new record in Zoho CRM
	 * @param $module                   string  The module in which the record will reside
	 * @param $update_data              array   The new data for the record
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 * @param $allow_duplicates         boolean Determines whether a duplicate record is allowed.
	 * @param $workflow_trigger         boolean Determines whether the new record triggers any relavant workflows.
	 *
	 * @return string   Newly generated record ID.
	 * @throws ZohoException
	 *
	 * https://crm.zoho.com/crm/private/xml/Modlue/insertRecords?authtoken=AuthToken&scope=crmapi&xmlData=Your XML Data
	 **/
	public function insert_record($module, $update_data, $extra_post_parameters = array(), $allow_duplicates = false, $workflow_trigger = false)
	{
		$xmldata = $this->XMLfy(array($update_data), $module);
		if ($workflow_trigger) {
			$workflow_trigger='true';
		} else {
			$workflow_trigger='false';
		}
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'xmlData' => $xmldata,
			'wfTrigger' => $workflow_trigger,
			'newFormat' => 1,
			'version' => 2
		);
		if (!$allow_duplicates) $post['duplicateCheck'] = 2;
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/xml/$module/insertRecords", $q);
		$this->check_successful_xml($response);
		// Return the newly generated record ID:
		$html = new DOMDocument();
		$html->loadXML($response);
		$search_nodes = $html->getElementsByTagName( "FL" );
		foreach( $search_nodes as $currentNode )
		{
			$valueID = $currentNode->getAttribute('val');
			if ("Id"==$valueID) return $currentNode->nodeValue;
		}
		throw new ZohoException('Record ID not found!');
	}


	/**
	 *
	 * Check if a XML is correct
	 *
	 * @param $response
	 * @return bool
	 * @throws ZohoException
	 */
	protected function check_successful_xml($response) {
		$html = new DOMDocument();
		$html->loadXML($response);
		/** @var DOMDocument $err */
		if ($err = $html->getElementsByTagName('error')->item(0)) {
			throw new ZohoException($err->getElementsByTagName('message')->item(0)->nodeValue);
		}
		return true;
	}


	/**
	 * @param $lead_ID
	 * @param $potential_data           array
	 * @param $extra_post_parameters    array   Used to add or override post parameters.
	 * @param $allow_duplicates         boolean Determines whether a duplicate record is allowed.
	 * @return mixed
	 * @throws ZohoException
	 * https://crm.zoho.com/crm/private/json/Leads/convertLead?authtoken=Auth_Token&scope=crmapi&leadId=entity_Id&xmlData=POTENTIALXMLDATA
	 */
	public function convert_lead($lead_ID, $potential_data, $extra_post_parameters = array(), $allow_duplicates = false)
	{
		$this->ensure_opened();
		$xmldata = $this->XMLfy(array($potential_data), "Potentials");
		$post = array(
			'authtoken' => $this->authtoken,
			'scorpe' => 'crmapi',
			'leadId' => $lead_ID,
			'xmlData' => $xmldata,
			'newFormat' => 1,
			'version' => 2
			//2018-3-9 removed the following argument.  Not shown in Zoho document, need to verify that it exist:
			//'wfTrigger' => 'true'
		);
		if (!$allow_duplicates) $post['duplicateCheck'] = 2;
		$post = array_merge($post, $extra_post_parameters);
		$q = http_build_query($post);
		$response = $this->openUrl("{$this->domain}/crm/private/json/Leads/convertLead", $q);
		return $this->json_to_array($response, $module);
	}


	/**
	 *  Uploads a file to a record in Zoho CRM
	 * @param $module       string  The module in which the file will be uploaded
	 * @param $record_id    string  The record in which the file will be uploaded
	 * @param $filename     string  The path an filename of the file to be uploaded.
	 *
	 * @return array
	 * @throws ZohoException
	 *
	 * https://crm.zoho.com/crm/private/json/Module/uploadFile?authtoken=Auth_Token&scope=crmapi&id=Record_Id&content=File Input Stream
	 **/
	public function upload_file($module, $record_id, $filename)
	{
		if (version_compare(phpversion(), '5.5', '<')) {
			// php version isn't high enough
			$cFile = "@$filename;type=".mime_content_type($filename);
		}
		else{
			$cFile = new CurlFile( $filename,  mime_content_type($filename) );
		}
		$post = array(
			'authtoken' => $this->authtoken,
			'scope' => 'crmapi',
			'id' => $record_id,
			'content' => $cFile);

		$response = $this->openUrl("{$this->domain}/crm/private/json/$module/uploadFile", $post);
		if (FALSE === $response) throw new Exception(curl_error($ch), curl_errno($ch));
		return $this->json_to_array($response, $module);
	}


	/**
	 * Uses curl to open a URL
	 *
	 * @param $url
	 * @param null $data
	 * @return mixed|null
	 */
	protected function openUrl($url, $data = null)
	{
		$this->zoho_log("URL:\t" . $url);
		$this->zoho_log("Data:\t" . print_r($data,true));
		$ch = curl_init();

		if ($data) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		$this->zoho_log("Reply:\t" . $data);
		return $data;
	}


	/**
	 * Add an entry to the debug log
	 *
	 * @param $message  string  The text that will be added to the debug log
	 */
	protected function zoho_log($message)
	{
		if (null==$this->debug_log) return;
		error_log(date("Y-m-d H:i:s") . " " . $message . PHP_EOL, 3, $this->debug_log);
	}


	/**
	 * @param   $arr    array
	 * @param   $module string
	 * @return string
	 */
	protected function XMLfy($arr, $module)
	{
		$xml = "<$module>";
		$no = 1;
		foreach ($arr as $a) {
			$xml .= "<row no=\"$no\">";
			foreach ($a as $key => $val) {
				$xml .= "<FL val=\"$key\"><![CDATA[" . trim($val) . "]]></FL>";
			}
			$xml .= "</row>";
			$no += 1;
		}
		$xml .= "</$module>";
		return $xml;
	}


	/**
	 * @param $response_data        string|object|array
	 * @param $module               string      The module from which the record will be retrieved
	 * @param $unique_as_multiple   boolean     if True, the value returned will be in a multidimentional array even if there is only 1 record
	 *
	 * @return array
	 * @throws ZohoException
	 */
	protected function json_to_array($response_data, $module, $unique_as_multiple = false)
	{
		$array_contents = array();
		if (is_object($response_data)) {
			$response_data = json_decode(json_encode($response_data), true);
		} else if (is_string($response_data)) {
			$response_data = json_decode($response_data, true);
		}

		if (isset($response_data['response']['result'][$module]['row'][0])) {
			$entries = $response_data['response']['result'][$module]['row'];
			foreach ($entries as $entry) {
				$array_contents[] = $this->get_entry_value($entry);
			}
			return $array_contents;
		} elseif (isset($response_data['response']['result']['recorddetail'])) {
			return $response_data['response']['result']['message'];
		} elseif (isset($response_data['response']['error']))
			throw new ZohoException('Zoho returned error '.$response_data['response']['error']['code'].': '.$response_data['response']['error']['message'],$response_data['response']['error']['code']);
		else{
			if (!isset($response_data['response']['result'])) return array();
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
		foreach ($entry['FL'] as $key => $record) {
			$array_contents[$record['val']] = $record['content'];
		}
		return $array_contents;
	}}

