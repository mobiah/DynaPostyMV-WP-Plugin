<?php
/*
Plugin Name: Dynaposty Dynamic Landing Pages
Plugin URI: http://www.mobiah.com/
Description: Dynamic content in your posts and pages, based on referring searches and links
Version: 0.2
Author: Mobiah 
*/

/*  Copyright 2010  Mobiah http://www.mobiah.com
*/

/*
*	GLOBAL PATHS and variables
*
*/
define("DDEBUG", true);
define("MDEBUG", false);
define("DYPO_PATH", dirname(__FILE__));
$pathExploded = explode( '/', DYPO_PATH );
define("DYPO_URL", WP_PLUGIN_URL.'/'.$pathExploded[ count($pathExploded)-1 ]);
define("DYPO_IMG_URL", DYPO_URL.'/images');
define("DYPO_JS_URL", DYPO_URL.'/js');
global $wpdb;
define("DYPO_SHORTCODE_TABLE", $wpdb->prefix."dypo_shortcodes");
define("DYPO_NUM_SHORTCODES", 10);
define("DYPO_COOKIE_NAME", 'dypo_urlname');
define("DYPO_COOKIE_VAL", 'dypo_urlvar');
define("DYPO_VERSION", "0.2");
define("DYPO_REPORTING_ACTION", 'dypo_reporting');
define("DYPO_REPORTING_FREQ", 'hourly');
define("DYPO_REPORTING_URL", 'http://www.mobiah.com/dynaposty/reporting.php');

/*
*	Includes
*/
if ( is_admin() ){
	include_once( DYPO_PATH.'/dypo-admin.php' );
	include_once( DYPO_PATH.'/dypo-config.php' );
	include_once( DYPO_PATH.'/dypo-install.php' );
}
include_once( DYPO_PATH.'/dypo-reporting.php' );	// gets triggered by regular user page visit
include_once( DYPO_PATH.'/dypo-functions.php' );
include_once( DYPO_PATH.'/dypo-hooks.php' );


/*
*	Options stored in wp-options table
*/
define ('DYPO_OPTIONS', 'dypo_options');
define ('DYPO_OPTIONS_CODE_PREFIX', 'dypo_code_');
define ('DYPO_OPTIONS_REFRESH', 'dypo_refresh');
define ('DYPO_OPTIONS_RETEST', 'dypo_retest');
define ('DYPO_STATS', 'dypo_stats');
global $dypo_options, $dypo_stats;  // the array which holds all options
$dypo_options = get_option( DYPO_OPTIONS, array());
$dypo_stats = get_option( DYPO_STATS, array());
if(MDEBUG) { error_log("dynaposty dypo_stats=" . var_export($dypo_stats,true) . "\n"); }


/*
//$retval = add_filter(‘cron_schedules’, ‘every_minute’);
$retval = add_filter(‘cron_schedules’, array(&$this, 'every_minute'));
if(DDEBUG) { error_log("add_filter retval=$retval"); }
function every_minute( $schedules ) {
	if(DDEBUG) { error_log('every_minute = ' . var_export($schedules,true) . '\n'); }
	$schedules['minute'] = array( 'interval' => 60, 'display' => __('Once per Minute'));
	if(DDEBUG) { error_log('every_minute = ' . var_export($schedules,true) . '\n'); }
	return $schedules;
}
*/
//print_r(wp_get_schedules());
//dypo_report();

/*
// look for a unique identifier for this dynaposty installation.  In the future this may be replaced by
// actual registration for an API key.  For now, if we dont' find a key, generate a random one.
global $dypo_key;
if ( is_array($dypo_options) && array_key_exists( 'dypo_key', $dypo_options ) && $dypo_options['dypo_key'] != '' ) {
	$dypo_key = $dypo_options['dypo_key'];
} else {
	$dypo_key = dypo_randomString( 25 );  // make a key of random alphanumeric characters, length 25
	$dypo_options['dypo_key'] = $dypo_key;  // put it in the options array
	update_option( DYPO_OPTIONS, $dypo_options ); // and save it back to the options table
}
*/

// this variable tells whether or not we have run the server environment compatibility tests.
// if it is blank, they have not been run.  one can set it to blank to re-run the tests.
global $dypo_envTest;
if ( is_array($dypo_options) && array_key_exists( 'dypo_envTest', $dypo_options ) ) { $dypo_envTest = $dypo_options['dypo_envTest']; } 
else { $dypo_envTest = ''; }

// should we set a cookie to keep the user getting the same values for a given amount of time?
global $dypo_setCookie;
if ( is_array($dypo_options) && array_key_exists( 'dypo_setCookie', $dypo_options ) ) { $dypo_setCookie = $dypo_options['dypo_setCookie']; } 
else { $dypo_setCookie = false; }

// if so, how long should the cookie stick around?
global $dypo_cookieExpire;
if ( is_array($dypo_options) && array_key_exists( 'dypo_cookieExpire', $dypo_options ) ) { $dypo_cookieExpire = $dypo_options['dypo_cookieExpire']; } 
else { $dypo_cookieExpire = 15;  /* defaults to 15 days */ }

global $dypo_values;


?>
