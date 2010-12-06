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
	global $dypo_setCookie, $dypo_cookieExpire, $dypo_values, $dypo_stats, $dypo_options;
	$dypo_values = dypo_getShortcodeValues();
	if(MDEBUG) { error_log("dypohooks dypo_init dypo_values=" . var_export($dypo_values,true) . "\n"); }

	if ( is_admin() ){
		if(DDEBUG) { error_log("dypohooks is_admin true"); }
		add_action('media_buttons','dypo_mediaButtonIcon',100); //- this should be the last thing to show.
		return;
	}

	/* create my lookup hashes */	
	foreach( $dypo_values as $ix => $myrow ) {
		$name = $myrow->urlname; $val = $myrow->urlvar; 
		$dypo_urlnames{$name} = 1;
		$dypo_urlvalues{$val} = 1;
		$dypo_urlnamevalues{"$name-$val"} = $ix;		// index of this unique name/value pair
	}
	if(DDEBUG) { error_log("dypohooks dypo_init dypo_urlnames=" . var_export($dypo_urlnames,true) . "\n"); }
	if(DDEBUG) { error_log("dypohooks dypo_init dypo_urlvalues=" . var_export($dypo_urlvalues,true) . "\n"); }
	$refVars = $_GET; // this is when the querystring we care about is *this* page's querystring
	if(DDEBUG) { error_log("dypohooks dypo_init refVars=" . var_export($refVars,true) . "\n"); }

	/*** loop over the users URL vars (refVars) and see if they exist in my lookup hashes, if yes, update var 'ix' ****/
	$ix = 0;
	foreach($refVars as $refname => $ok) {
		$refval = $refVars[$refname];
		if(DDEBUG) { error_log("dypohooks dypo_init refname=$refname : refval=$refval"); }
		if(array_key_exists($refname, $dypo_urlnames) && strlen($refval) > 0 && array_key_exists($refval, $dypo_urlvalues)) {
			if(array_key_exists("$refname-$refval",$dypo_urlnamevalues)) {
				$ix = $dypo_urlnamevalues{"$refname-$refval"}; 
				if(DDEBUG) { error_log("dypohooks dypo_init found refname=$refname and refval=$refval ix=$ix"); }
				$finalname = $refname; 
				$finalval = $refval;
			}
		}
	}
		/*
		if ( $dypo_setCookie ) { 
			setCookie( DYPO_URLVAR_COOKIE, $urlValue, time() + 60*60*24*$dypo_cookieExpire ); 
		}
		*/
	
	if(DDEBUG) { error_log("dypohooks dypo_init ix=$ix finalname=$finalname finalval=$finalval"); }
	if(empty($finalname) && empty($finalval)) {
		/* nothing found so far, check cookies if required */
		if ($dypo_setCookie && array_key_exists(DYPO_COOKIE_NAME,$_COOKIE) && array_key_exists(DYPO_COOKIE_VAL,$_COOKIE) && strlen($_COOKIE[DYPO_COOKIE_NAME]) > 0 && strlen($_COOKIE[DYPO_COOKIE_VAL]) > 0){
			$finalname = $_COOKIE[DYPO_COOKIE_NAME];
			$finalval = $_COOKIE[DYPO_COOKIE_VAL];
			if(array_key_exists("$finalname-$finalval",$dypo_urlnamevalues)) { $ix = $dypo_urlnamevalues{"$finalname-$finalval"}; }
			if(DDEBUG) { error_log("dypohooks dypo_init from cookie: finalname=$finalname : finalval=$finalval ix=$ix"); }
		}
	}
	else {
		/* if we found a URL var match (not using default), then update my stats */
		$dypo_stats[$finalval] = array_key_exists($finalval,$dypo_stats) ? $dypo_stats[$finalval]+1 : 1; // stats: increment usage counter for this urlValue
		update_option( DYPO_STATS, $dypo_stats );
		/* set cookie if required */
		if($dypo_setCookie) {
			if(DDEBUG) { error_log("dypohooks dypo_init setting cookie: finalname=$finalname : finalval=$finalval"); }
			setCookie(DYPO_COOKIE_NAME,$finalname,time() + 60*60*24*$dypo_cookieExpire); 
			setCookie(DYPO_COOKIE_VAL,$finalval,time() + 60*60*24*$dypo_cookieExpire); 
		}
	}

	/* create a shortcode function for ALL shortcodes, doesn't matter if we have a urlValue or not */

	if(MDEBUG) { error_log("dypohooks dypo_init using index $ix"); }
	$shortcodes = array();
	$dbnames = array();
	for($i=0; $i < DYPO_NUM_SHORTCODES; $i++) { $c = DYPO_OPTIONS_CODE_PREFIX . $i; $shortcodes[]= $dypo_options[$c]; $j=$i+1; $dbnames[] = "code$j"; }
	//for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) { $oldshortcodes[] = "dynacode_$i"; $olddbnames[] = "code$i"; }
	if(DDEBUG) { error_log("dypohooks dypo_init shortcodes=" . var_export($shortcodes,true) . '\n'); }
	if(MDEBUG) { error_log("dypohooks dypo_init dbnames=" . var_export($dbnames,true) . '\n'); }
	$row = $dypo_values[$ix];
	//$size = count($dbnames);
	if(MDEBUG) { error_log("dypohooks dypo_init row=" . var_export($row,true) . '\n'); }
	for ($i=0; $i < DYPO_NUM_SHORTCODES; $i++) {
		$shortcode = $shortcodes[$i];
		$field = $dbnames[$i];
		// must escape double quotes here, because lambda func below is wrapped in quotes
		$rf = preg_replace('/\"/','\\"',$row->$field);
		//$rf = preg_replace('/\'/','&#39;',$rf);
		$fun = ' return "' . $rf . '";';
		if(MDEBUG) { error_log("dypohooks dypo_init shortcode:$shortcode: fun=$fun"); }
		$newFunc = create_function('',$fun); //create a lambda-style function which returns the value.
		if(MDEBUG && $newFunc()) { error_log("dypohooks dypo_init shortcode=$shortcode field=$field output=" . $newFunc()); }
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
	global $dypo_options;
	$dypo_shortcodes = array();
	for($i=0; $i < DYPO_NUM_SHORTCODES; $i++) { $c = DYPO_OPTIONS_CODE_PREFIX . $i; $dypo_shortcodes[]= $dypo_options[$c]; }
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
