<?php
/*
*	DynaPosty home-calling functions
*	
*/

/*
*	Gathers data and uses wordpress's post function to submit all data to a server
*	This includes: plugin name, plugin version, admin-selected options, server environment info, api key, and domain
*/
function dypo_report($install = false) {
	global $dypo_options, $dypo_stats;
	if(DDEBUG) { error_log("DYPO_report cron job install=$install"); }
	try {
	$postData = array();
	$postData['timeout'] = 10;
	$postData['body'] = array();
	$postData['body']['domain'] = $_SERVER['HTTP_HOST'];
	$postData['body']['serveradmin'] = $_SERVER['SERVER_ADMIN'];
	$postData['body']['version'] = DYPO_VERSION;
	$postData['body']['setCookie'] = $dypo_options['dypo_setCookie'];
	$postData['body']['cookieExpire'] = $dypo_options['dypo_cookieExpire'];
	if(DDEBUG) { error_log("DYPO_report cron postData=" . var_export($postData,true) . '\n'); }
	if(count($dypo_stats) > 0) { $postData['body']['stats'] = serialize($dypo_stats); }
	if($install) { $postData['body']['environment'] = serialize(dypo_environmentInfo()); }
	if(DDEBUG) { error_log("DYPO_report cron final postData=" . var_export($postData,true) . '\n'); }
	$doReport = wp_remote_post(DYPO_REPORTING_URL, $postData);
	if(DDEBUG) { error_log("DYPO_report cron doReport=" . var_export($doReport,true) . '\n'); }
	return $doReport;
	}
	catch(Exception $ex) { 
		if(DDEBUG) { error_log("DYPO_report ex=$ex"); }
	}
}

add_action(DYPO_REPORTING_ACTION, 'dypo_report');

/*
*	Gather info about the Server Environment
*/
function dypo_environmentInfo() {
	$serverInfo = array();
	
	$serverInfo['wp_version'] = get_bloginfo( 'version' );
	$serverInfo['wp_charset'] = get_bloginfo( 'charset' );
	$serverInfo['phpversion'] = phpversion();
	//$serverInfo['phpsettings'] = ini_get_all();
	//$serverInfo['phpextensions'] = get_loaded_extensions();
	//$serverInfo['_SERVER'] = $_SERVER;
	if(DDEBUG) { error_log("dypo_envInfo =" . var_export($serverInfo,true) . "\n"); }
	
	return $serverInfo;
}
?>
