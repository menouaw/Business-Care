<?php
/* curl -X POST -H "Authorization: Bearer $(gcloud auth print-access-token)" -H "Content-Type: application/json; charset=utf-8" -d (Get-Content -Raw -Path "C:/MAMP/htdocs/Business-Care/api/admin/ai/request.json") "https://europe-west9-aiplatform.googleapis.com/v1beta1/projects/esgi-bc/locations/europe-west9/endpoints/openapi/chat/completions"
*/
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

define('PROJECT_ID', 'esgi-bc');
define('LOCATION_ID', 'europe-west9');
define('API_ENDPOINT', 'endpoints/openapi/chat/completions');
define('MODEL_ID', 'gemini-2.0-flash-lite-001');
define('MODEL_NAME', 'google/' . MODEL_ID);
define('GENERATE_CONTENT_API', 'streamGenerateContent');

define('API_HOST', 'europe-west9-aiplatform.googleapis.com');
define('API_PATH', 'v1beta1/projects/' . PROJECT_ID . '/locations/' . LOCATION_ID . '/endpoints/openapi/chat/completions');
