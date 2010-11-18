<?php
/*
*	Dynaposty configuration page
*/

add_action('admin_menu', 'dypo_configPage');
function dypo_configPage() {
	if ( function_exists('add_menu_page') )
		add_menu_page(__('DynaPosty Settings'), __('DynaPosty'), 'manage_options', 'dypo_config', 'dypo_configDisplay', DYPO_IMG_URL.'/icon_dynamite_17x15.png');
		add_submenu_page( 'dypo_config', __('DynaPosty Readme'), __('Help'), 'manage_options', 'dynaposty-readme', 'dynapostyReadme');
}


///////////////////////////////////////////
function dynapostyReadme() {
	$file = dirname(__FILE__)  . '/help.html';
	if(DDEBUG) { error_log("dypo-config:dynapostyReadme file=$file"); }
	$readme = file_get_contents($file);
	$msg = <<<PAGE
<div class="wrap">
	<div class="tool-box">$readme
	</div>
</div>
PAGE;
	print $msg;
}


///////////////////////////////////////////
function dypo_configDisplay() {
	global $dypo_envTest, $dypo_setCookie, $dypo_cookieExpire, $dypo_values;

	if ( array_key_exists( 'dypo_csvUpload', $_FILES ) ) { dypo_handleUpload(); }
	$size = count($dypo_values);
	if(DDEBUG) { error_log("dypo-config: dypo_values size=$size"); }

?>
<div class="wrap">
	<div class="icon32" style="background:url('<?=DYPO_IMG_URL?>/icon_dynamite_40x35.png') no-repeat transparent;"><br/></div>
	<h2 style="clear:none;"><?_e("DynaPosty Settings");?></h2> 
	<div id="dypo_optionsContainer">
		<? if ($dypo_envTest == '' || $_GET['dypo_doEnvTest']=='true') {
			dypo_envTester();
			add_action('admin_footer','dypo_congrats');
		} ?>
		<div class="dypo_messageContainer">
			<div id="dypo_contentLoading" style="display:none;"><img alt="" id="ajax-loading" src="images/wpspin_light.gif"/></div>
			<div id="dypo_contentMessage" class="dypo_message <?=($dypo_envTest == 'failure' ? 'dypo_error_message' : '')?>" <?=($dypo_envTest == 'failure' ? '' : 'style="display:none;"')?> ><?=($dypo_envTest == 'failure' ? __('Warning - your server configuration may prevent the normal function of DynaPosty.').'(<a href="'.$_SERVER["REQUEST_URI"].'&dypo_doEnvTest=true">'.__('Click to test again').'</a>)' : '&nbsp;')?></div>
		</div>
		<div id="dypo_mainSettings">
			<table class="form-table">
			<tr>
				<th scope="row"> <label for="dypo_setCookie"> <?_e('Save shortcode in a cookie');?>?  </label> </th>
				<td style="width: 100px;"><input type="checkbox" name="dypo_setCookie" id="dypo_setCookie" value="true" <?=( $dypo_setCookie ? 'checked="checked"' : '' )?> /></td>
				<th scope="row"> <label for="dypo_cookieExpire"> <?_e('Save the cookie for ');?>?  </label> </th>
				<td>
					<select id="dypo_cookieExpire" name="dypo_cookieExpire">
						<option value="15">15 <?_e('days');?> </option>
						<option value="30">30 <?_e('days');?> </option>
						<option value="60">60 <?_e('days');?> </option>
						<option value="90">90 <?_e('days');?> </option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="4">
				<div style="margin:8px;">Tip: The first row is the default row. See help for more info.</div>  
				<table class="widefat" style="border:0px solid blue;" width="100%" id="dypo_shortcodeSettings">
				<thead>
				<tr class="dypo_editRow" title="<?_e('eg. utm_content');?>">
					<th class="dypo_TableTitle" > <?_e('URL variable name');?> </th>
					<th class="dypo_TableTitle" title="<?_e('pick a value');?>"> <?_e('URL variable value');?> </th>
					<?php for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) {	?>
					<th class="dypo_TableTitle" title="<?_e('dynaposty shortcode');?>"> <?_e("dynacode_$i");?> </th>
					<?php } ?>
				</tr>
				</thead>
				<?php
				foreach( $dypo_values as $vsID => $myrow ) {
					$name = $myrow->urlname;
					$val = $myrow->urlvar;
					$myclass = $vsID == 0 ? 'dypo_lightRow' : '';
					//$s1 = $myrow->code1; $s2 = $myrow->code2; $s3 = $myrow->code3 || '';
					if(MDEBUG) { error_log("dypo-config: vsID=$vsID : name=$name"); }
				?>
				<tr id="<?=$vsID?>" class="dypo_row<?=$vsID?> dypo_editRow <?=$myclass?>" >
					<td class="dypo_editable dypo_strong" >
						<span id="dypo_val_<?=$vsID?>|urlname"><?=$name?></span>
						<input class="dypo_textInput" type="text" id="dypo_edit_<?=$vsID?>|urlname" value="<?=$name?>" />
						<? if ( $vsID > 0 ) { ?>
						<a href="#" onClick="if (confirm('<?_e('Delete Row');?>?')) dypo_delValSet(this); return false;" title="<?_e('Delete Row');?>" class="dypo_delete">X</a>
						<? } // end if ?>
					</td>
					<td class="dypo_editable" >
						<span id="dypo_val_<?=$vsID?>|urlvar"><?=$val?></span>
						<input class="dypo_textInput dypo_noSpaces" type="text" id="dypo_edit_<?=$vsID?>|urlvar" value="<?=$val?>" />
					</td>
					<?php for($i=1; $i <= DYPO_NUM_SHORTCODES; $i++) {	
						$mycol = "code$i"; 
						$mycode = $myrow->$mycol; 
						$myval = "$vsID|$mycol";
					?>
					<td class="dypo_editable" >
						<span id="dypo_val_<?=$myval?>"><?=$mycode?></span>
						<input class="dypo_textInput dypo_noSpaces" type="text" id="dypo_edit_<?=$myval?>" value="<?=$mycode?>" />
					</td>
					<?php } ?>
				</tr>
				<?php
				} // end foreach( $dypo_values as ..)
				?>
				<tr>
					<td colspan='100'>
					<a href="#" onClick="dypo_newValueSetRow('dypo_val_','dypo_edit_','dypo_row'); return false;"><?_e('Add a row');?> &darr;</a> 
					</td>
				</tr>
				</table>
				</td>
			</tr>
			<tr>
				<th scope="row"> <?_e('Upload a ');?> .csv : </th>
				<td>
					<form action="" method="POST" enctype="multipart/form-data">
					<input type="file" id="dypo_csvUpload" name="dypo_csvUpload" value="" />
					<input type="submit" value="Upload and Preview" />
					</form>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<input type="submit" id="dypo_saveAll" name="dypo_saveAll" class="button-primary dypo_saveAll" value="Save All Settings" />
				</th>
			</table>
			<script type="text/javascript">
			//<![CDATA[
				// save all settings on this page.
				jQuery(document).ready( function(){ 
					// make cells editable when clicked. and give them a title which says that they are editable
					console.debug("dypo-config: jQuery ready"); 
					jQuery('.dypo_editable').attr('title','click to edit').click( function () { dypo_editCell(this); });

					// set the function which confirms when the user leaves the page without saving
					window.onbeforeunload = dypo_contentOnUnload;

					// set the function which saves all the data
					jQuery('.dypo_saveAll').click( function () {
						console.debug("dypo-config: jQuery dypo_saveAll");
						dypo_sanitizeInput( 'dypo_mainSettings', 'dypo_noSpaces' );
						// check for duplicate URL variables
						if (dypo_findDupeURLVars('dypo_edit_')) {
							dypo_showMessage( "<?_e('Duplicate URL variables detected, which is not allowed.  Settings NOT saved.');?>", 'dypo_contentMessage', false, true);
							return;
						}

						// reset all the open editable cells back to not being edited.
						dypo_resetEdits('dypo_editable', 'dypo_strong');

						var values = dypo_buildValues('dypo_edit_');
						//console.debug("dypo-config: dypo_saveAll values="); console.dir(values);

						// send the info off our dypo-admin:dypo_saveOptions via wordpress ajax.
						dypo_ajax( ajaxurl, 
									{ 	"action" : 'dypo_saveOptions',
										"dypo_setCookie" : jQuery('#dypo_setCookie').get(0).checked.toString(),
										"dypo_cookieExpire" : jQuery('#dypo_cookieExpire').val(),
										"dypo_values" : jQuery.toJSON(values),
									},
									'dypo_contentMessage',
									'dypo_contentLoading',
									false
									);
						dypo_unsavedEdits = false; // no unsaved edits - no need to ask the user about leaving.

					} );
				} );
			//]]></script>
		</div> <!-- end dypo_mainSettings -->
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<?=( $dypo_envTest == 'success' ? __('Your server environment passed the DynaPosty compatibility test.').' <a href="'.$_SERVER["REQUEST_URI"].'&dypo_doEnvTest=true">'.__('Click to test again').'</a>.' : '' )?>
	</div> <!-- end dypo_optionsContainer -->
</div> <!-- end wrap -->


<?php
} 


////////////////////////////// csv file upload //////////////////////
function dypo_handleUpload() {
		$fileMessage = '';
		$fileError = false;
		if ( $_FILES['dypo_csvUpload']['error'] == 0 ) {
			// okay, no error, let's try to parse this csv.  this is where the work is done.
			$parseResult = dypo_parseCSV( $_FILES['dypo_csvUpload']['tmp_name']); // pass in the tmp file name
			if ( $parseResult != '' ) {
				$fileMessage = __('We were unable to properly parse your CSV file.  Error: '.$parseResult);
				$fileError = true;
			} 
			else { $fileMessage = __('Here are the results of your upload. If you do not \"Save all Settings\", these values will not be saved.'); }
		} 
		else {
			$fileMessage = __('A problem occured uploding your CSV - did you choose a file?');
			$fileError = true;
		}
		if ( $fileMessage != '' ) {
			?>
			<script type="text/javascript"> 
				jQuery(document).ready( function () { dypo_showMessage( "<?=$fileMessage?>", 'dypo_contentMessage', false, <?=( $fileError ? 'true' : 'false' )?> ); } );
			</script>
			<?
		}
}

?>
