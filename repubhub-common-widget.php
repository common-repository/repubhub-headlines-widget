<?php
/**
 * @file
 * Common functions for communicating with the iCopyright servers
 */

// Which iCopyright server should we talk to via REST? The standard is license.icopyright.net, port 80,
// but you can target alternate infrastructures (normally for debugging purposes) by changing these variables.

if(!defined("RPH_WIDGET_SERVER")) define("RPH_WIDGET_SERVER", "license.icopyright.net");
if(!defined("RPH_WIDGET_PORT")) define("RPH_WIDGET_PORT", 80);


/**
 * Return the iCopyright server and port that is handling the various services
 *
 * @param bool $secure
 *      should we go over https?
 * @return the full server specification
 */
function rph_widget_get_server($secure = FALSE, $includeHttp = TRUE) {
	$server = "";
	
	if ($includeHttp) {
	  $server = ($secure ? 'https:' : 'http:');
	}
	
	$server .= '//' . RPH_WIDGET_SERVER;

  if (RPH_WIDGET_PORT != 80) {
    $server .= ':' . RPH_WIDGET_PORT;
  }
  return $server;
}

function rph_widget_static_server($includeHttp = FALSE) {
	$url = $includeHttp ? "http://" : "//";

	if (RPH_WIDGET_SERVER == "staging.icopyright.net") {
		return $url . 'static.staging.icopyright.net';
	}
	else {
		return $url . 'static.icopyright.net';
	}
}

function rphWidgetGetPortal() {
	if (RPH_WIDGET_SERVER == "staging.icopyright.net") {
		return 'https://portal.icopyright.net';
	}	else {
		return 'https://repubhub.icopyright.net';
	}
}


function rph_widget_get_embed($tag, $allowScript, $useragent, $email, $password) {
	$endPoint = $allowScript ? "embed" : "oembed";
  $url = "/api/xml/repubhub/".$endPoint."?tag=".$tag;
  $res = rph_widget_post($url, NULL, $useragent, rph_widget_make_header($email, $password), "GET");
  return $res;
}


/**
 * Checks the response object for success code. Returns true if all is OK.
 *
 * @param  $res
 *      The response from a post
 * @return TRUE if all is OK
 */
function rph_widget_check_response($res) {
  return ($res->http_code == '200');
}


/**
 * Given an email address and a password, create the appropriate headers for authentication to change
 * Conductor settings
 *
 * @param  $email
 *      the email address of the user
 * @param  $password
 *      the user's iCopyright password
 * @return headers to use
 */
function rph_widget_make_header($email, $password) {
	if ($email == NULL && $password == NULL) {
		return null;
	}
	
  $header_encode = base64_encode("$email:$password");
  return array('Authorization' => 'Basic '.$header_encode);
}

/**
 * General helper function to post RESTfully to iCopyright. Returns an object with the following
 * fields: response (the text back from the server); http_code (the code, like 200 or 404); http_expl
 * (the http string corresponding to that code); curl_code (the curl error code)
 *
 * @param $url
 *      the URL to post to
 * @param $postdata
 *      the data that we're sending up
 * @param $useragent
 *      the user agent doing the requesting -- should be the plugin and version number
 * @param $headers
 *      headers to include for authentication, if any
 * @param $method
 *      the HTTP method -- defaults to post of course
 * @return object results of the post as specified
 */
function rph_widget_post($url, $postdata, $useragent = NULL, $headers = NULL, $method = 'POST') {
  return rph_widget_blocking($url, $postdata, $useragent, $headers, $method, true);
}

function rph_widget_blocking($url, $postdata, $useragent = NULL, $headers = NULL, $method = 'POST', $blocking) {

    //Default: timeout: 5, redirection: 5, httpversion: 1.0, blocking: true, headers: array(), body: null, cookies: array()
    $args = array();
    $args['method'] = $method;
    $args['timeout'] = 60;
    $args['redirection'] = 5;
    $args['httpversion'] = '1.0';
    $args['blocking'] = $blocking;
    $args['sslverify'] = false;

    if ($headers == NULL)
        $headers = array();

    if($postdata != NULL) {
        $args['body'] = $postdata;
    } else {
        $args['body'] = NULL;
    }

    // Very unlikely we will need to follow, but set if we can
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        $args['redirection'] = 1;
    }

    if ($useragent != NULL) {
        $args['user-agent'] = $useragent;
    }

    $args['headers'] = $headers;
    $args['cookies'] = array();

    // Fetch the respopnse
    $rv = new stdClass();

    $response = wp_remote_post( rph_widget_get_server(TRUE) . $url, $args);
    
    if( is_wp_error( $response ) ) {
        $rv->http_expl = $response->get_error_message();
    } else {

        $rv->response = $response['body'];
        $rv->http_code = $response['response']['code'];

        // A 200 code can carry an error message in the payload
        if($rv->http_code == 200) {
            $xml = @simplexml_load_string($rv->response);
            $status = $xml->status;
            $rv->http_code = (string)$status['code'];
        } else if ($rv->http_code == 401) {
        	// Unauthorized
        	$rv->response = '<h3>HTTP Status 401 — Bad credentials</h3><p>Please ask your administrator to verify that his/her email address and password are valid and updated for the iCopyright plugin.  The administrator can verify this by visiting the iCopyright plugin settings page and clicking Show Advanced Settings.   If he/she set a password for www.repubhub.com, he/she must use the same password.</p>';
        }

        $responses = array(
            100 => 'Continue', 101 => 'Switching Protocols',
            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
            300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
            400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed',
            500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported'
        );

        if ($rv->http_code) {
        	$rv->http_expl = $responses[$rv->http_code];
        }
    }

    return $rv;
}
