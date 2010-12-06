<?
/*
*	DynaPosty Functions
*/

/*
*	tells us if we're on a dynaposty Admin page
*/
function isDyPoAdminPage() {
	if ( strpos( $_SERVER["SCRIPT_NAME"], '/wp-admin' ) === false ){
		// if the URL somehow doesn't have /wp-admin, this is not a dynaposty admin page
		return false;
	}
	if ( strpos( $_SERVER["SCRIPT_NAME"], 'page.php' ) !== false 
		|| strpos( $_SERVER["SCRIPT_NAME"], 'page-new.php' ) !== false 
		|| strpos( $_SERVER["SCRIPT_NAME"], 'post.php' ) !== false 
		|| strpos( $_SERVER["SCRIPT_NAME"], 'post-new.php' ) !== false 
		|| strpos( $_SERVER["SCRIPT_NAME"], 'post-new.php' ) !== false 
		|| strpos( $_SERVER["SCRIPT_NAME"], 'post-new.php' ) !== false 
		|| strpos( $_SERVER["QUERY_STRING"], 'page=dypo_config' ) !== false 
		) {
		return true;
	}
}


// save shortcode values from the browser into the database
function dypo_saveValues ( $values, $titles ) {
	global $wpdb, $dypo_options;
	$size = count($values);
	$tsize = count($titles);
	if(DDEBUG) { error_log("dyna:functions:saveValues tsize=$tsize : size=$size"); }
	if(MDEBUG) { error_log("dyna:functions:saveValues tsize=$tsize : size=$size : values=" . var_export($values,true) . '\n'); }
	if(MDEBUG) { error_log("dyna:functions:saveValues tsize=$size : titles=" . var_export($titles,true) . '\n'); }

	foreach ( $titles as $t )  { $atitles[] = $t; }		// convert stdClass to one-dim array
	for($i=0; $i < DYPO_NUM_SHORTCODES; $i++) { $c = DYPO_OPTIONS_CODE_PREFIX . $i; $dypo_options[$c] = $atitles[$i]; }
	$dypo_options[DYPO_OPTIONS_REFRESH] = true;
	update_option( DYPO_OPTIONS, $dypo_options );

	$data = '';
	foreach ( $values as $myrow )  {
		if(MDEBUG) { error_log("dyna:functions:saveValues : myrow=" . var_export($myrow,true) . '\n'); }
		$data .= "(id";
		foreach ($myrow as $key => $val )  {
			if(MDEBUG && !empty($val)) { error_log("dyna:functions:saveValues : key=$key : val=$val"); }
			$d = addslashes($val);
			$data .= ",'$d'";
		}
		$data .= "),";
	}
	$data = rtrim($data,',');
	$insert = "insert into " . DYPO_SHORTCODE_TABLE . " values $data";
	if(DDEBUG) { error_log("dyna:functions:saveValues insert=$insert"); }
	$del = "DELETE FROM " . DYPO_SHORTCODE_TABLE;
	$wpdb->query($del);
	$wpdb->query($insert);
	dypo_reloadPage();
}

/// reloadPage: so that we get the delete links if any new rows were added
function dypo_reloadPage() {
	//setTimeout(function(){ top.location.href=top.location.href; }, 3000); }	// not working??
	//setTimeout( function(){ jQuery("#saveMess").hide(4000); }, 5000 );
	?>
	<script type="text/javascript"> 
	top.location.href=top.location.href;
	</script>
	<?php
}


function dypo_parseCSV ( $filename ) {
	global $wpdb,$dypo_options;
    ini_set('auto_detect_line_endings', 1);
	define('COLUMNS',DYPO_NUM_SHORTCODES + 2);	// without id field
	if(DDEBUG) { error_log("dyna:functions:dypo_parseCSV filename=$filename COLUMNS=" . COLUMNS); }
	$handle = fopen( $filename, "r");
	if ($handle !== false) {
		$dup = false;
		$dups = array();
		while (($data = fgetcsv($handle, 3000)) !== FALSE) {
			if(DDEBUG) { error_log("dyna:functions:dypo_parseCSV data=" . var_export($data,true) . '\n'); }
			$numcol = count($data);
			if(DDEBUG) { error_log("dyna:functions:dypo_parseCSV numcol=$numcol data[0]=$data[0]"); }
			if($numcol < 3 || empty($data[0])) { continue; }
			$filler = COLUMNS - $numcol;
			$value .= "(id,";
			$dupkey = $data[0] . $data[1];
			if(DDEBUG) { error_log("dyna:functions:dypo_parseCSV dupkey=$dupkey"); }
			if($dups{$dupkey}) { $dup = true; break; }
			else { $dups{$dupkey} = 1; }
			foreach ( $data as $field )  { $field = addslashes($field); $value .= "'$field',"; }
			for($i=0;$i < $filler;$i++) { $value .= "'',"; }
			$value = rtrim($value,',');
			$value .= "),";
		}
		$value = rtrim($value,',');
		if(DDEBUG) { error_log("dyna:functions: dup=$dup : value = $value"); }
		fclose($handle);

		if($dup) { return "Found duplicate name-value pair: $data[0] - $data[1]"; }

		$insert = "insert into " . DYPO_SHORTCODE_TABLE . " values $value";
		if(DDEBUG) { error_log("dyna:functions:parseCSV insert=$insert"); }
		$wpdb->query($insert);
		$dypo_options[DYPO_OPTIONS_REFRESH] = true;
		update_option( DYPO_OPTIONS, $dypo_options );
		dypo_reloadPage();
		return ''; // no error message 
	} else {
		// couldn't open the file?
		fclose($handle);
		return 'Unable to read file.';
	}
}
 
// get shortcode values from the database.
function dypo_getShortcodeValues ( $where = ' 1 ' ) {
	global $wpdb;
	$sql = "SELECT * FROM ".DYPO_SHORTCODE_TABLE." WHERE $where ";
	if(MDEBUG) { error_log("dyna:functions:dypo_getShortcodeValues sql=$sql"); }
	return (array)($wpdb->get_results($sql));
}

// useful for debugging
if ( !function_exists( 'pre' ) ) {
function pre( $value = '!@#$%^&*()_!@#$%^&*()' ) {

	if ( $value === '!@#$%^&*()_!@#$%^&*()' ) {
		// dummy value, show the stack trace
		echo('<pre>'.var_export(debug_backtrace(),true).'</pre>'."<br />\n");
		return;
	}
	echo('<pre>'.var_export($value,true).'</pre>'."<br />\n");
}
}


function dypo_json_decode($json, $assoc = false) { 
	// at the moment, the $assoc = false argument does nothing but make this function
	// have the same number of arguments as the original json_decode
    // Author: walidator.info 2009
    $comment = false;
	$x = NULL;
    $out = '$x=';
   
    for ($i=0; $i<strlen($json); $i++)
    {
        if (!$comment)
        {
            if ($json[$i] == '{')        $out .= ' array(';
            else if ($json[$i] == '}')    $out .= ')';
            else if ($json[$i] == ':')    $out .= '=>';
            else                         $out .= $json[$i];           
        }
        else $out .= $json[$i];
        if ($json[$i] == '"')    $comment = !$comment;
    }
    eval($out . ';');
    return $x;
}

/**
*  Use this function to parse out the query array element from
*  the output of parse_url().
*/
function dypo_parseQuery($var){
	$var  = parse_url($var, PHP_URL_QUERY);
	$var  = html_entity_decode($var);
	$var  = explode('&', $var);
	$arr  = array();

	foreach($var as $val) {
		$x = explode('=', $val);
		$arr[$x[0]] = $x[1];
	}
	unset($val, $x, $var);
	return $arr;
}

/*
*	random string generator
*/
function dypo_randomString($length = 10, $letters = '1234567890qwertyuiopasdfghjklzxcvbnm') {
	$s = '';
	$lettersLength = strlen($letters)-1;
	for($i = 0 ; $i < $length ; $i++) {
		$s .= $letters[rand(0,$lettersLength)];
	}
	return $s;
} 
?>
