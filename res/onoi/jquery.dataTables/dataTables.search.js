/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */

/*global jQuery */
( function ( $ ) {

	'use strict';

	/**
	 * @since 3.0
	 *
	 * @param {String} text
	 *
	 * @return {String}
	 */
	function replace( text ) {
		return text
			.replace(/[áÁàÀâÂäÄãÃåÅæÆâ]/g, 'a')
			.replace(/[çÇ]/g, 'c')
			.replace(/[éÉèÈêÊëËê]/g, 'e')
			.replace(/[íÍìÌîÎïÏîĩĨĬĭ]/g, 'i')
			.replace(/[ñÑ]/g, 'n')
			.replace(/[óÓòÒôÔöÖœŒ]/g, 'o')
			.replace(/[ß]/g, 's')
			.replace(/[úÚùÙûÛüÜ]/g, 'u')
			.replace(/[ýÝŷŶŸÿ]/g, 'n')
			.replace( /έ/g, 'ε' )
			.replace( /[ύϋΰ]/g, 'υ' )
			.replace( /ό/g, 'ο' )
			.replace( /ώ/g, 'ω' )
			.replace( /ά/g, 'α' )
			.replace( /[ίϊΐ]/g, 'ι' )
			.replace( /ή/g, 'η' )
			.replace( /\n/g, ' ' )
			.replace( /á/g, 'a' )
			.replace( /é/g, 'e' )
			.replace( /í/g, 'i' )
			.replace( /ó/g, 'o' )
			.replace( /ú/g, 'u' )
			.replace( /ê/g, 'e' )
			.replace( /î/g, 'i' )
			.replace( /ô/g, 'o' )
			.replace( /è/g, 'e' )
			.replace( /ï/g, 'i' )
			.replace( /ü/g, 'u' )
			.replace( /ã/g, 'a' )
			.replace( /õ/g, 'o' )
			.replace( /ç/g, 'c' )
			.replace( /ì/g, 'i' );
	}

	/**
	 * @see https://github.com/DataTables/Plugins/blob/master/filtering/type-based/accent-neutralise.js
	 *
	 * @param  {[type]} data [description]
	 * @return {[type]}      [description]
	 */
	$.fn.dataTable.ext.type.search.string = function ( data ) {
	    return ! data ? '' : typeof data === 'string' ? replace( data ) : data;
	};

	/**
	 * @see https://datatables.net/plug-ins/filtering/type-based/html
	 * @see https://datatables.net//forums/discussion/comment/98704/#Comment_98704
	 */
	var _div = document.createElement('div');

	$.fn.dataTable.ext.type.search.html = function( data ) {
		_div.innerHTML = data;
		return _div.textContent ? replace( _div.textContent ) : replace( _div.innerText );
	};

}( jQuery ) );
