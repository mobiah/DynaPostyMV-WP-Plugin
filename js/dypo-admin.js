/*
*	Dynaposty Admin Functions 
*/

var DDEBUG = true;

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

		/*
// build a list of setnames from the table in the page
function dypo_buildValueSets ( vsPrefix ) {
	if(DDEBUG) { console.debug('buildValueSets vsPrefix=' + vsPrefix); }
	vsObject = {};
	counter = 1;
	jQuery('[id^='+vsPrefix+']').each( function () {
		vsObject[counter] = this.value;
		counter++;
	} );
	if(DDEBUG) { console.dir(vsObject); }
	return vsObject;
}

// build a 2-dimensional array of all the shortcode values that have been entered.
function dypo_buildValues ( idPrefix, scPrefix, vsPrefix ) {
	if(DDEBUG) { console.debug('buildValues idPrefix=' + idPrefix + ' scPrefix=' + scPrefix); }
	valObject = {};
	jQuery('[id^='+idPrefix+']').each( function () {
		id = this.id;
		valSet = id.substring( idPrefix.length, id.indexOf('|') );
		if(DDEBUG) { console.debug('buildValues id=' + id + ' valSet=' + valSet); }
		shortcodeIndex = id.substring( id.indexOf('|') + 1 );
		shortcode = jQuery( '#'+scPrefix+'\\|'+shortcodeIndex ).val();
		if ( typeof(shortcode) == 'undefined' ) { shortcode = shortcodeIndex; }
		if(DDEBUG) { console.debug('buildValues shortcodeIndex=' + shortcodeIndex + ' shortcode=' + shortcode); }
		newValue = this.value;
		if(DDEBUG) { console.debug('buildValues valSet=' + valSet + ' value=' + this.value); }
		
			// the sub-array doesn't exist, let's make it.
		if ( typeof(valObject[valSet]) == 'undefined' ) { valObject[valSet] = {}; }
		valObject[valSet][shortcode] = newValue;
	} );
	if(DDEBUG) { console.dir(valObject); }
	return valObject;
}
		*/
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

// find duplicate URL variables
function dypo_findDupeURLVars ( valEditPrefix ) {
	varObj = {};
	foundDupe = false;
	jQuery(':input[id$=urlvar]').each( function () {
		if ( this.value in varObj ) { foundDupe = true; }
		varObj[this.value] = '.';
	} );
	return foundDupe;
}

/*
// find the current maximum shortcode Index in the table
function dypo_getMaxShortcodeIndex ( scPrefix ) {
	curMax = 1;
	jQuery('span[id^='+scPrefix+']').each( function () {
		id = this.id;
		shortcodeIndex = Number(id.substring( id.indexOf('|') + 1 ));		
		curMax = Math.max( curMax, shortcodeIndex );
	} );
	return curMax;
}
*/

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

// creates a new column (shortcode) at the right of each row that is editable
// once again, with dummy values and properly set ids

/*
function dypo_newShortcodeCol( scNamePrefix, scPrefix, columnClassPrefix ) {
	curMaxShortcode = dypo_getMaxShortcodeIndex( scPrefix );
	newSCIndex = String(Number(curMaxShortcode)+1);

	jQuery('#dypo_shortcodeSettings .dypo_editable:last-child').each( function () {
		// create the new element - with event handlers
		newCell = jQuery(this).clone(true).insertAfter(jQuery(this));
		oldSpanID = newCell.find('span').attr('id');
		oldInputID = newCell.find(':input').attr('id');
		if ( oldSpanID.indexOf(scNamePrefix) != -1 ) {
			// shortcode Name
			newVal = 'Shortcode Name';
		} else if ( oldSpanID.indexOf(scPrefix) != -1 ) {
			// shortcode
			newVal = 'shortcode'+newSCIndex;
		} else {
			// shortcode value
			newVal = 'value';
		}
		newSpanID = oldSpanID.replace('|'+String(curMaxShortcode),'|'+newSCIndex);
		newInputID = oldInputID.replace('|'+String(curMaxShortcode),'|'+newSCIndex);
		
		// update the new values and ids
		newCell.find('span').html(newVal).attr('id',newSpanID);
		newCell.find(':input').val(newVal).attr('id',newInputID);
		
		// and give it the right class to represent its column number
		newCell.removeClass(columnClassPrefix+curMaxShortcode).addClass(columnClassPrefix+newSCIndex);
	} );
	
	dypo_unsavedEdits = true; // make sure they confirm before exiting
}
*/

// deleting rows (value sets)  easy, because we just delete a row.
function dypo_delValSet( domObj ) {
	// remove the row containing the object that was clicked
	jQuery(domObj).parent().parent().remove();
}
/*
// deleting columns (shortcodes) harder, because we have to delete separate things
function dypo_delShortcode( domObj, columnClassPrefix ) {
	// get the classes from the parent cell
	classes = jQuery(domObj).parent().attr('class');
	// now, get the class which represents the column number
	colIndexClass = classes.substring(classes.indexOf(columnClassPrefix));
	if (colIndexClass.indexOf(' ') != -1) {
		// remove trailing spaces, and anything that comes after spaces.
		colIndexClass = colIndexClass.substring(0,colIndexClass.indexOf(' '));
	}
	// zzzzzzaap!
	jQuery('.'+colIndexClass).remove();
}
*/

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
