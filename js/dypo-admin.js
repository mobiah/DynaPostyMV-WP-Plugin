/*
*	Dynaposty Admin Functions 
*/

var DDEBUG = false;

// decides whether to show a confirmation about leaving the page, if the user has changed
// content settings, and has not saved them.
var dypo_unsavedEdits = false;
function dypo_contentOnUnload () {
	if ( typeof(dypo_unsavedEdits) == 'boolean' && dypo_unsavedEdits ) {
		return "You may have changed some Dynaposty Settings without saving.  Are you sure?";
	}
}

// when a user clicks on a table cell, we assume they want to edit it.
// hide the span in the td, and show the editable form field
function dypo_editCell ( cell ) {
	if(DDEBUG) { console.debug('dypo_editCell cell=' + cell.id); }
	jQuery(cell).find('span').hide();
	jQuery(cell).find(':input').show().focus();
	dypo_unsavedEdits = true;
	return;
}

// make all editable cells go back to spans instead of inputs
// and make sure the span holds the same value as the edited input
function dypo_resetEdits ( classname, strongClassname ) {
	if(DDEBUG) { console.debug('dypo_resetEdits classname=' + classname); }
	jQuery('.'+classname).find(':input').hide().each( function () {
		inputVal = this.value;
		if ( jQuery(this).parent().hasClass(strongClassname) ){
			inputVal = '<strong>'+inputVal+'</strong>';
		}
		jQuery(this).parent().find('span').html(inputVal).show();
	}) ;
}

// build a list of shortcodes from table in the page
// also looks for duplicates.  if one is found, returns false.
function dypo_buildShortcodes ( scnPrefix, scPrefix ) {
	if(DDEBUG) { console.debug('dypo_buildShortCodes scnPrefix=' + scnPrefix + ' scPrefix=' + scPrefix); }
	scObject = {};
	jQuery('[id^='+scnPrefix+']').each( function () {
		try {
		// get the index so we can find the corresponding shortcode
		id = this.id;
		shortcodeIndex = id.substring( id.indexOf('|') + 1 );
		if(DDEBUG) { console.debug('dypo_buildShortCodes id=' + id + ' shortcodeIndex=' + shortcodeIndex); }
		
		// get the name entered
		shortcodeName = jQuery(this).val();
		// now get the value from the corresponding shortcode in the next row
		var tmp = '#'+scPrefix+'\\|'+shortcodeIndex;
		shortcode = jQuery( '#'+scPrefix+'\\|'+shortcodeIndex ).val();
		if(DDEBUG) { console.debug('dypo_buildShortCodes shortcodeName=' + shortcodeName + ' shortcode=' + shortcode + " tmp=" + tmp); }
		
		// have we already seen this shortcode?  if so, return false.  
		if ( shortcode in scObject ){
			scObject = false;
			return false;
		}
		
		// put 'em in the object.
		scObject[shortcode] = shortcodeName;
		}
		catch(ex) { if(DDEBUG) { console.debug('dypo_buildShortCodes ex=' + ex); }}
	} );
	return scObject;
}

function dypo_buildTitles ( idPrefix ) {
	if(DDEBUG) { console.debug('buildTitles idPrefix=' + idPrefix); }
	valObject = {};
	var column = 0;
	jQuery('[id^='+idPrefix+']').each( function () {
		//if(DDEBUG) { console.debug('buildTitles column=' + column + ' value=' + this.value); }
		valObject[column] = this.value;
		column++;
	} );
	return valObject;
}

function dypo_buildValues ( idPrefix ) {
	if(DDEBUG) { console.debug('buildValues idPrefix=' + idPrefix); }
	valObject = {};
	jQuery('[id^='+idPrefix+']').each( function () {
		id = this.id;
		row = id.substring( idPrefix.length, id.indexOf('|') );
		column = id.substring( id.indexOf('|') + 1 );
		if(DDEBUG) { console.debug('buildValues id=' + id + ' row=' + row + ' column=' + column + ' value=' + this.value); }
		if ( typeof(valObject[row]) == 'undefined' ) { valObject[row] = {}; } // add row to array if needed.
		valObject[row][column] = this.value;
	} );
	return valObject;
}

function dypo_multiName() { 
	if(DDEBUG) { console.debug('multiName'); }
	var count = 0;
	varObj = {};
	jQuery(':input[id$=urlname]').each( function () {
		if(this.value in varObj) { }
		else {
			count++;
			varObj[this.value] = '.'; 
			if(DDEBUG) { console.debug('multiName adding ' + this.value + ' count=' + count); }
		}
	} );
	if(count > 1) { return true; }
	return false;
}

// find duplicate URL variables
var currDup;
function dypo_getDup() { return currDup; }
function dypo_findDupeURLVars ( valEditPrefix ) {
	if(DDEBUG) { console.debug('findDupeURLVars valEditPrefix=' + valEditPrefix); }
	varObj = {}; names = []; values = [];
	dup = false;
	var ix = 0; jQuery(':input[id$=urlname]').each( function () { names[ix] = this.value; ix++; });
	ix = 0; jQuery(':input[id$=urlvar]').each( function () { values[ix] = this.value; ix++; });
	for(i=0; i < ix; i++) {
		var key = names[i] + values[i];		// combo of url name + url value
		if(DDEBUG) { console.debug('findDupeURLVars key=' + key); }
		if(key in varObj) { dup = true; currDup = names[i] + ' - ' + values[i]; }
		varObj[key] = '.';
	}
	if(DDEBUG) { console.debug('findDupeURLVars dup=' + dup); }
	return dup;
}

// find the current maximum valueSet Index in the table
//function dypo_getMaxValueSetIndex ( vsPrefix ) {
function dypo_getMaxValueSetIndex ( valPrefix ) {
	//curMax = 1;
	curMax = 0;
	if(DDEBUG) { console.debug('getMaxValueSetIndex valPrefix=' + valPrefix); }
	jQuery('span[id^='+valPrefix+']').each( function () {
		id = this.id;
		var filter = /[^0-9]/g; 
		var tmp = id.substring( 0,id.indexOf('|'));
		var ix = Number(tmp.replace(filter,''));
		//shortcodeIndex = Number(id.substring( id.indexOf('|') + 1 ));		
		if(DDEBUG) { console.debug('getMaxValueSetIndex id=' + id + ' ix=' + ix + ' tmp=' + tmp); }
		curMax = Math.max( curMax, ix );
	} );
	return curMax;
}
/*
*/

// some values in the page shouldn't have anything but 
// * alphanumeric
// * underscores
// * dashes
// * no leading and trailing whitespace, either.
// * shortcodes and urlvars also cannot have spaces.
// this function gets rid of anything we decide we don't want.  
// basically, anything in a text input and not in a textarea
function dypo_sanitizeInput( divID, noSpaceClass ) {
	if(DDEBUG) { console.debug('sanitizeInput divID=' + divID + ' class=' + noSpaceClass); }
	jQuery('#'+divID+' :text').each( function () {
		if ( jQuery(this).hasClass(noSpaceClass) ) {
			this.value = this.value.replace(/[^a-zA-Z0-9\-\_]+/g,'');
		} else {
			this.value = jQuery.trim(this.value.replace(/[^a-zA-Z 0-9\-\_]+/g,''));
		}
	} );
}

// creates a new row (value set) at the end of the table with copy of values of previous row 
// and properly names ids for spans and inputs.
function dypo_newValueSetRow ( valPrefix, valEditPrefix, rowClassPrefix ) {
	try {
		if(DDEBUG) { console.debug('newValueSetRow currix=' + currix + ' valPrefix=' + valPrefix + ' valEditPrefix=' + valEditPrefix + ' rowClassPrefix=' + rowClassPrefix); }
		var currix = dypo_getMaxValueSetIndex(valPrefix);
		var newix = currix + 1;
		if(DDEBUG) { console.debug('newValueSetRow currix=' + currix + ' newix=' + newix); }
		newRow = jQuery('#dypo_shortcodeSettings tr#'+currix).clone(true).insertAfter('#dypo_shortcodeSettings tr#'+currix); // clone a new table row 
		newRow.attr('id',newix).removeClass(rowClassPrefix+currix).addClass( rowClassPrefix+newix );
		if(DDEBUG) { console.dir(newRow); }

	/*
		////////////////////
		// DOES NOT WORK: replaces html correctly, but loses all event handlers (onclick ...)
		////////////////////
		var old_id = new RegExp(valPrefix + currix,'g');
		var new_id = valPrefix + newix;
		var old_edit_id = new RegExp(valEditPrefix + currix,'g');
		var new_edit_id = valEditPrefix + newix;
		if(DDEBUG) { console.debug('newValueSetRow old_id=' + old_id + ' new_id=' + new_id); }
		var html = newRow.html();
		html = html.replace(old_id,new_id);
		html = html.replace(old_edit_id,new_edit_id);
		newRow.html(html);
		if(DDEBUG) { console.debug('newValueSetRow new html=' + newRow.html()); }
	*/

		////////////////////
		// THIS WORKS: replaces only the element id's 
		////////////////////
		newRow.find('span[id^='+valPrefix+']').each( function() {
			var tail = this.id.substring( this.id.indexOf('|') + 1 );
			this.id = valPrefix+newix+'|'+tail;
		} );
	
		newRow.find('input[id^='+valEditPrefix+']').each( function() {
			var tail = this.id.substring( this.id.indexOf('|') + 1 );
			this.id = valEditPrefix+newix+'|'+tail;
		} );	

		if(DDEBUG) { console.debug('newValueSetRow new html=' + newRow.html()); }
		dypo_unsavedEdits = true; // make sure they confirm before exiting
	}
	catch(ex) { if(DDEBUG) { console.debug('newValueSetRow ex=' + ex); } }
}


// deleting rows (value sets)  easy, because we just delete a row.
function dypo_delValSet( domObj ) {
	// remove the row containing the object that was clicked
	jQuery(domObj).parent().parent().remove();
}

// show a message to the user on the main admin/config page, then fade it out.
// give it a message and a div to dump the message into
function dypo_showMessage( strMsg, divID, useFadeOut, isError, timeOut, fadeTime ) {

	if ( typeof(strMsg) == 'undefined' || strMsg.length == 0 ) {
		// no message to show?  don't do anything.
		return;
	}
	if ( typeof(divID) == 'undefined' ) {
		divID = 'dypo_message';
	}
	if ( typeof(useFadeOut) == 'undefined' ) {
		useFadeOut = true;
	}
	if ( typeof(isError) == 'undefined' ) {
		isError = false;
	}
	if ( typeof(timeOut) == 'undefined' ) {
		timeOut = 3000;  // wait for a default of 3 seconds.
	}
	if ( typeof(fadeTime) == 'undefined' ) {
		fadeTime = 1000; // fade for a default of 1 second
	}
	
	if ( isError ) {
		jQuery('div#'+divID).addClass('dypo_error_message');
	} else {
		jQuery('div#'+divID).removeClass('dypo_error_message');
	}
	// show the confirmation/message
	jQuery('div#'+divID).html(strMsg).show(); 
	// resize the container.
	jQuery('div#'+divID).parent().height(jQuery('div#'+divID).outerHeight());
	// then maybe set a timeout to let the div disappear
	if ( useFadeOut ) {
		setTimeout( function(){ jQuery('div#'+divID).fadeOut(fadeTime); }, timeOut );
	}
}

// send an ajax request to a URL.
// and show/hide the 'loading' div and result message container
function dypo_ajax ( url, data, msgDivID, loadingDivID, useFadeOut ) {

	if ( typeof(msgDivID) == 'undefined' ) {
		msgDivID = 'dypo_message';
	}
	if ( typeof(loadingDivID) == 'undefined' ) {
		loadingDivID = 'dypo_loading';
	}
	if ( typeof(useFadeOut) == 'undefined' ) {
		useFadeOut = true;
	}

	// hide any existing messages
	jQuery('div#'+msgDivID).hide();
	// post to the url specified
	jQuery.post( url , data,
					function ( strMsg ){  
						// upon completion/callback hide the spinning/loading animation
						jQuery('div#'+loadingDivID).hide(); 
						// and show the message returned.
						dypo_showMessage( strMsg, msgDivID, useFadeOut );
					} );
	// show the spinning/loading animation while we wait.
	jQuery('div#'+loadingDivID).show();
}

// one lonely function for the content editor pages.
function dypo_insertShortcode( selectID ) {

	var win = window.dialogArguments || opener || parent || top;
	win.send_to_editor('['+jQuery('#'+selectID).val()+']');

	jQuery('#'+selectID).get(0).selectedIndex = 0;
}
