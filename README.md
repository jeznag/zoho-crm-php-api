# zoho-crm-php-api
An easy to use PHP library for Zoho CRM

Known Bugs
==============
json_to_array() doesn't work when you feed it multiple records

Sample usage:
==============

$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';

$zoho_api = new Zoho($AUTH_TOKEN);

$potential_data = array();
$potential_data['Account Name'] = "ACCOUNT NAME";
$potential_data['Potential Name'] = "POTENTIAL NAME";
$potential_data['Amount'] = "20";
$potential_data['Stage'] = "Closed Won";
$potential_data['Potential Owner'] = "allyourbasearebelong@to.us";

if ($status != "Approved")
	$potential_data['Closing Date'] = date('m/d/Y', strtotime("+30 days"));
else
	$potential_data['Closing Date'] = date('m/d/Y', strtotime("+0 days"));

//have to wrap the array in another array for some reason
$response = $zoho_api->insert_record('Potentials', (array($potential_data)));
