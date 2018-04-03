# Release Notes

## v2.2.0 (2018-04-03)

### Added
 * Added CHANGELOG.md
### Changed
 * Updated README.md with additional examples.

## v2.1.0 (2018-03-19:)

### Added

 * Allow the user to set timeout.
 * Allow the user to choose if insert/update record(s) triggers workflows.


## v2.0.0 (2018-03-09)

### Added

 * Added debug logging.
 * Added get_records_by_searching to retrieve multiple records.
 * Added update_records to allow for update of multiple records with single API call
 * Added ability to select sort column in get_records
 * Added error checking to json_to_array to catch all returned Zoho errors
 * Added upload_file function.
 
 ### Changed
 * Improved documentation.
 * Removed unused $auth_url
 * Removed $ticket which was from prior version of API
 * Change all Zoho URLs to use json when available, convert XML results to json when only XML URL is available.
 * Allow for inserting duplicate records in insert_record
