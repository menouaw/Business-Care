<?php
/* curl -X POST -H "Authorization: Bearer $(gcloud auth print-access-token)" -H "Content-Type: application/json; charset=utf-8" -d '@request.json' "https://aiplatform.googleapis.com/v1/projects/esgi-bc/locations/global/publishers/google/models/gemini-2.5-flash-preview-04-17:streamGenerateContent"
*/
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

define('PROJECT_ID', 'esgi-bc');
define('LOCATION_ID', 'global');
define('API_ENDPOINT_HOST', 'aiplatform.googleapis.com');
define('MODEL_ID', 'gemini-2.5-flash-preview-04-17');
define('PUBLISHER', 'google');
define('GENERATE_CONTENT_API_METHOD', 'streamGenerateContent');


define('API_PATH_PREFIX', 'v1/projects/' . PROJECT_ID . '/locations/' . LOCATION_ID . '/publishers/' . PUBLISHER . '/models/');


// "https://aiplatform.googleapis.com/v1/projects/esgi-bc/locations/global/publishers/google/models/gemini-2.5-flash-preview-04-17:streamGenerateContent"
