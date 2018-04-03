# zoho-crm-php-api
An easy to use PHP library for Zoho CRM API v1.

NB - API v1 is deprecated and will be turned off in December 2019. If you're starting a new project, you're better off using the official PHP SDK for API v2: https://www.zoho.com/crm/help/developer/server-side-sdks/php.html

## Examples:
**Sample usage of insert_record:**

```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
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
$response = $zoho_api->insert_record('Potentials', $potential_data);
```

**Sample usage of get_record_by_searching:**
```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';  
$zoho_api = new Zoho($AUTH_TOKEN);  
$email = "allyourbasearebelong@to.us";  
$zoho_result = zoho_api->get_record_by_searching('Leads', "(Email:$email)");
```

**Sample usage of get_record_by_id:**
```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';  
$zoho_api = new Zoho($AUTH_TOKEN); 
$ID = '1234567000001234001';
$zoho_result = zoho_api->get_record_by_id('Leads', $ID);
```

**Sample usage of update_record:**
```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';  
$zoho_api = new Zoho($AUTH_TOKEN);
$ID = '1234567000001234001'; 
$new_values=array();
$new_values['Lead Status']='Pending';
$new_values['Phone']='(800) 555-1212';
zoho_api->update_record('Leads', $ID, $new_values);
```

**Sample usage of upload_file:**
```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';  
$zoho_api = new Zoho($AUTH_TOKEN);
$ID = '1234567000001234001'; 
$filename='/path/to/file.ext';
zoho_api->upload_file('Leads', $ID, $filename);
```


**Sample usage of get_records and update_records:**
```php
include_once 'zoho-crm-php-api/zoho_methods.php';  
$AUTH_TOKEN = 'INSERT AUTH TOKEN HERE';  
$zoho_api = new Zoho($AUTH_TOKEN);
$lead_index=1;
while (true) {
    $to_index = $lead_index+99;
    $response = $zoho_api->get_records('Leads',$lead_index,$to_index,[],false,'Created_Time',true);
    $lead_count=0;
    if (0==count($response)) break;
    $zoho_data = array();
    foreach ($response as $current_lead) {
        $record = array();
        $record['Id'] = $current_lead['LEADID'];
        $record['Lead Status']='Pending';
        $zoho_data[] = $record;
    }
    $zoho_api->update_records('Leads', $zoho_data);
    $lead_index+=100;
}
```
