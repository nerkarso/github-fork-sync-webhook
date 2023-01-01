<?php

// Secrets
define('GH_TOKEN', '');
define('USER_AGENT', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4863.0 Safari/537.36 Edg/100.0.1163');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
header('Content-Type: application/json');

// Check allowed methods
switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    try {
      $params = get_query_params();
      $payload = get_payload();
      $params['branch'] = $payload['branch'];
      echo trigger_merge($params);
    } catch (Exception $ex) {
      echo json_encode(['error' => $ex->getMessage()]);
    }
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => '405 Method Not Allowed']);
    break;
}

function get_query_params()
{
  if (!isset($_GET['repo'])) {
    throw new Exception('The `repo` query parameter is missing, example: ?repo=octocat/Hello-World');
  }
  if (empty($_GET['repo'])) {
    throw new Exception('The `repo` query parameter cannot be empty, example: ?repo=octocat/Hello-World');
  }

  return $_GET;
}

function get_payload()
{
  $result = array('branch' => 'master');
  $json = json_decode(file_get_contents('php://input'), true);

  if ($json['ref']) {
    $result['branch'] = end(explode('/', $json['ref']));
  }

  return $result;
}

function trigger_merge($params)
{
  $curl = curl_init();
  $repo = $params['repo'];
  $branch = $params['branch'];

  curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.github.com/repos/$repo/merge-upstream",
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_POSTFIELDS => '{ "branch": "' . $branch . '" }',
  CURLOPT_HTTPHEADER => array(
      'Accept: application/vnd.github.v3+json',
      'Authorization: token ' . GH_TOKEN,
      'Content-Type: application/json',
      'User-Agent: ' . USER_AGENT
    ),
  )
  );

  $response = curl_exec($curl);
  curl_close($curl);

  return $response;
}