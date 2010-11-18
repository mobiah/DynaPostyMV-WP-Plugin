<?php
/*
*	Dynaposty Wordpress Hooks  
*/

/*
*	AJAX test.  This function does nothing but help confirm that an AJAX request can be
*	successfully handled.  
*/
add_action('wp_ajax_dypo_ajaxTest', 'dypo_ajaxTest');
function dypo_ajaxTest() { echo "true"; die(); }

/*
*	Test results.  If everything works well in the dypo_envTest function (see dypo-admin.php)
*	page, then it will post to this function, causing it to save the "success" status.
*/
add_action('wp_ajax_dypo_envTestSuccess', 'dypo_envTestSuccess');
function dypo_envTestSuccess() {
	global $dypo_envTest, $dypo_options;
	if(DDEBUG) { error_log("dypohooks dypo_envTestSuccess"); }
	$dypo_envTest = 'success';
	$dypo_options['dypo_envTest'] = 'success';
	update_option( DYPO_OPTIONS, $dypo_options );
}

// this is where the magic happens.   Not really,  just string replacing.
// * loads all the values from the database
// * checks to see if the URL matches anything we're looking for. 
// * and then register some shortcode handlers to do the string replacing.
add_action('init','dypo_init');
function dypo_init ( $atts=null, $content=null ) {
	global $dypo_setCookie, $dypo_cookieExpire, $dypo_values, $dypo_stats;
	$dypo_values = dypo_getShortcodeValues();
	//if(DDEBUG) { error_log("dypohooks dypo_init dypo_values=" . var_export($dypo_values,true) . "\n"); }

	if ( is_admin() ){
		if(DDEBUG) { error_log("dypohooks is_admin true"); }
		add_action('media_buttons','dypo_mediaButtonIcon',100); //- this should be the last thing to show.
		return;
	}
	
	// check if the referrer's query string contains the val we're looking for
	// also, check the cookie to see if we've saved any indicator.

	foreach( $dypo_values as $ix => $myrow ) {
		$name = $myrow->urlname; $val = $myrow->urlvar; 
		$dypo_urlnames{$name} = 1;
		$dypo_urlvalues{$val} = $ix;
	}
	if(MDEBUG) { error_log("dypohooks dypo_init dypo_urlnames=" . var_export($dypo_urlnames,true) . "\n"); }
	if(MDEBUG) { error_log("dypohooks dypo_init dypo_urlvalues=" . var_export($dypo_urlvalues,true) . "\n"); }

	//$refVars = dypo_parseQuery($_SERVER['HTTP_REFERER']); // this is for checking referrer.  maybe later.
	$refVars = $_GET; // this is when the querystring we care about is *this* page's querystring
	if(DDEBUG) { error_log("dypohooks dypo_init refVars=" . var_export($refVars,true) . "\n"); }


	/************************************/
	// AW: 11/12/2010 TEMP: needs fix: for now just handling ONE url variable name (because of cookie code below)
	$dypo_URLVar = '';
	foreach( $dypo_urlnames as $name => $ok) {
		if(array_key_exists($name,$refVars) && strlen($refVars[$name]) > 0) { $dypo_URLVar = $name; break; }
	}
	if(DDEBUG) { error_log("dypo-hook: init URLVar=$dypo_URLVar"); }
	/************************************/

	$urlValue = ''; // lets try to fill this variable now.
	if ( array_key_exists( $dypo_URLVar, $refVars ) && strlen( $refVars[$dypo_URLVar] ) > 0 ) {
		$urlValue = $refVars[$dypo_URLVar];
		if ( $dypo_setCookie ) { 
			if(DDEBUG) { error_log("dypohooks dypo_init urlValue=$urlValue dypo_setCookie = $dypo_setCookie setting cookie"); }
			setCookie( DYPO_URLVAR_COOKIE, $urlValue, time() + 60*60*24*$dypo_cookieExpire ); 
		}
	} 
	elseif ( $dypo_setCookie && array_key_exists( DYPO_URLVAR_COOKIE,$_COOKIE) && strlen($_COOKIE[DYPO_URLVAR_COOKIE]) > 0){ 
		$urlValue = $_COOKIE[DYPO_URLVAR_COOKIE]; 
		if(DDEBUG) { error_log("dypohooks dypo_init getting urlValue=$urlValue from cookie"); }
	}
	if(DDEBUG) { error_log("dypohooks dypo_init urlValue=$urlValue"); }


	/* find the row for this urlValue, or use default 0 if urlValue not found */

	if($dypo_URLVar && array_key_exists($urlValue,$dypo_urlvalues)) { 
		$ix = $dypo_urlvalues{$urlValue}; 
		$dypo_stats[$urlValue] = array_key_exists($urlValue,$dypo_stats) ? $dypo_stats[$urlValue]+1 : 1; // stats: increment usage counter for this urlValue
		update_option( DYPO_STATS, $dypo_stats );
	}
	else { $ix = 0; }


	/* create a shortcode function for ALL shortcodes, doesn't matter if we have a urlValue or not */

	if(DDEBUG) { error_log("dypohooks dypo_init using index $ix"); }
	$shortcodes = array();
	$dbnames = array();
	for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) { $shortcodes[] = "dynacode_$i"; $dbnames[] = "code$i"; }
	//if(DDEBUG) { error_log("dypohooks dypo_init shortcodes=" . var_export($shortcodes,true) . '\n'); }
	$row = $dypo_values[$ix];
	$size = count($dbnames);
	//if(DDEBUG) { error_log("dypohooks dypo_init row=" . var_export($row,true) . '\n'); }
	for ($i=0; $i < $size; $i++) {
		$shortcode = $shortcodes[$i];
		$field = $dbnames[$i];
		$fun = ' return "' . $row->$field . '";';
		$newFunc = create_function('',$fun); //create a lambda-style function which returns the value.
		if(DDEBUG && $newFunc()) { error_log("dypohooks dypo_init shortcode=$shortcode field=$field output=" . $newFunc()); }
		add_shortcode( $shortcode, $newFunc);
	}

}


// adds the shortcode inserter to an edit page
function dypo_mediaButtonIcon () {
	// make sure the hidden div gets displayed at the bottom.
	if(DDEBUG) { error_log("dypohooks dypo_mediaButtonIcon"); }
	add_action('admin_footer', 'dypo_shortcodeInserter');
?>	<a class="thickbox" href="#TB_inline?height=300&width=300&inlineId=dypo_selectShortcode" title="<?_e('Add a DynaPosty Shortcode');?>">
	<img src="<?=DYPO_IMG_URL.'/icon_dynamite_gray_15x14.png'?>" alt="<?_e('Add a DynaPosty Shortcode');?>" border="0" onmouseover="this.src='<?=DYPO_IMG_URL.'/icon_dynamite_15x14.png'?>';" onmouseout="this.src='<?=DYPO_IMG_URL.'/icon_dynamite_gray_15x14.png'?>';"/>
	</a>
<?
} // end function dypo_mediaButtonIcon


// shows the hidden div which allows the user to choose and actually insert a shortcode
// gets tacked on to the end of the page, and is invisible.  only shows up in thickbox 
// created by dypo_mediaButtonIcon code above.
function dypo_shortcodeInserter() {
	if(DDEBUG) { error_log("dypohooks dypo_shortcodeInserter"); }
	//global $dypo_shortcodes;
	$dypo_shortcodes = array();
	for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) { $dypo_shortcodes[] = "dynacode_$i"; }
?>
	<div id="dypo_selectShortcode" style="display:none; height: 300px; width:300px;">
		<h3>Insert a DynaPosty Shortcode:</h3>
		<select id="dypo_shortcodeSelect" >
<?
	foreach( $dypo_shortcodes as $sc ) {
		if(MDEBUG) { error_log("dypohooks dypo_shortcodeInserter sc=$sc"); }
?>
			<option value="<?=$sc?>"><?=$sc?>&nbsp;</option>
<?		
	}// end foreach
?>
		</select>
	<p>
	<input type="button" class="button-primary" value="Insert Shortcode" onclick="dypo_insertShortcode('dypo_shortcodeSelect');"/>&nbsp;&nbsp;
	<input type="button" class="button-secondary" value="Cancel" onclick="tb_remove(); return false;;"/>&nbsp;&nbsp;&nbsp;
	</p>
	</div><!-- end dypo_selectShortcode -->
	
<?
} // end function dypo_shortcodeInserter


?>
