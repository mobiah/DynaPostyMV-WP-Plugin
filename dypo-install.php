<?
/*
*	DynaPosty activation/deactivation hooks
*	Including: 
*		Database installation and/or upgrade for DynaPosty
*		registering reporting functions
*/

// for registering activation/deactivation functions, we need to pass in the main plugin file, which in our case, is the "includer" of this file.
$backtrace = debug_backtrace();
$mainFile = $backtrace[0]['file'];
if(MDEBUG) { error_log("dypoinstall: backtrace=$backtrace : mainFile=$mainFile"); }

// a couple of hooks to set up scheduled reporting routines (or remove them if deactivating) see dypo-hooks.php
register_activation_hook( $mainFile, 'dypo_addReporting' );
register_deactivation_hook( $mainFile, 'dypo_removeReporting');
register_deactivation_hook( $mainFile, 'dypo_uninstall');
register_activation_hook( $mainFile, 'dypo_install');

function dypo_install() {
	global $wpdb, $dypo_options;
	
	$table_name = DYPO_SHORTCODE_TABLE;
	if(DDEBUG) { error_log("dypoinstall: dypo_install"); }
	
	// we just need to install a table in the database - if it's already there, do nothing.
	if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		if(MDEBUG) { error_log("dyna:install: create table"); }
		$codes = '';
		for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) { $codes .= "code$i VARCHAR(200) DEFAULT '',"; }
		$sql = "CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			urlname VARCHAR(100) NOT NULL,
			urlvar VARCHAR(100) NOT NULL,
			$codes
			PRIMARY KEY  id (id),
			KEY urlname (urlname),
			KEY urlvar (urlvar)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		if(MDEBUG) { error_log("dyna:install: create table sql=$sql"); }
		$ret = dbDelta($sql);
			
		// record the database version
		$dypo_dbVersion = "1.0";
		$dypo_options['dypo_dbVersion'] = $dypo_dbVersion;
		// initialize shortcodes names to dynacode_ 
		for($i=0; $i < DYPO_NUM_SHORTCODES; $i++) { $c = DYPO_OPTIONS_CODE_PREFIX . $i; $j=$i+1; $dypo_options[$c] = "dynacode_$j"; }
		update_option( DYPO_OPTIONS, $dypo_options );
		
		// now, dump some values in for the first time, so that the admin page isn't empty when they first go there.
		$wpdb->insert( $table_name, array( 'urlname' => 'utm_content', 'urlvar'=>'', 'code1'=>'default'));

	} else {
		// the table exists, maybe in future versions, we'll need to change its structure here
		if(DDEBUG) { error_log("dyna:install: table exists"); }
	}

	dypo_report(true);	

}

////////////////////////////////////////
function dypo_uninstall() {
	global $wpdb;
	if(DDEBUG) { error_log("dypo-install: dypo_uninstall"); }
	$table_name = DYPO_SHORTCODE_TABLE;
	$sql = "DROP TABLE $table_name;";
	$wpdb->query($sql);
	delete_option(DYPO_OPTIONS);
	delete_option(DYPO_STATS);
}

?>
