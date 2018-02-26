var jq = jQuery.noConflict();

jq(document).ready(function() {
	console.log("MST jsem ve funkci document");
	var mojeurl = ajax_object_mila.ajax_url;
	var listy = "";

	var mojeoptions = [
		"Klara",
		"Kleopatra",
		"Petra",
		"Petr",
		"Adam",
		"Alena"
	];
	jq('input#cac_ncs_groups').Mojefce();
	// jq('input#cac_ncs_groups').autocomplete({
	// 	create: function( event, ui) {console.log("event create");}
	// });

	jq('input#cac_ncs_groups').Autocomplete( {
		source: mojeoptions,
		minLength: 2,
	});

	// jQuery('input#cac_ncs_groups').autocomplete("disable");

});

/**
*  Ajax Autocomplete for jQuery, version 1.1.3
*  (c) 2010 Tomas Kirda
*
*  Ajax Autocomplete for jQuery is freely distributable under the terms of an MIT-style license.
*  For details, see the web site: http://www.devbridge.com/projects/autocomplete/jquery/
*
*  Last Review: 04/19/2010
*/

/*jslint onevar: true, evil: true, nomen: true, eqeqeq: true, bitwise: true, regexp: true, newcap: true, immed: true */
/*global window: true, document: true, clearInterval: true, setInterval: true, jQuery: true */

(function($) {

	$.fn.Autocomplete = function(options) {
			console.log("MST jsem ve funkci fn.autocomplete");
			console.log(options);
			return "jmeno";
  	};
	$.fn.Mojefce = function() {
		console.log("mojefce");
	};

})(jQuery);
