/*!
 * This file is part of the Semantic MediaWiki Factbox module
 *
 * @since 3.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/*global jQuery, mediaWiki */
/*jslint white: true */

( function( $, mw ) {

	'use strict';

	/**
	 * Provides the sorting interface to `tinysort` to support sorting of rows
	 * for the `factbox-attachments` div-table.
	 *
	 * @see http://tinysort.sjeiti.com/#sorting-tables
	 */
	var table = document.getElementById( 'smw-factbox-attachments' );

	if ( table === null ) {
		return;
	};

	var	tableHead = table.getElementsByClassName( 'smw-table-header' )[0],
		tableHeaders = tableHead.querySelectorAll( 'div' ),
		tableBody = table.getElementsByClassName('smw-table-body')[0];

	tableHead.addEventListener( 'click', function( e ){

		var tableHeader = e.target,
			tableHeaderIndex,isAscending,order;

		tableHeaderIndex = Array.prototype.indexOf.call( tableHeaders, tableHeader );
		isAscending = tableHeader.getAttribute( 'data-order' ) === 'asc';
		order = isAscending ? 'desc' : 'asc';

		// Clean-up sort markers and attributes on all columns
		tableHeaders.forEach( function( item, index, arr ) {
			item.classList.remove( 'smw-table-sort-asc' );
			item.classList.remove( 'smw-table-sort-desc' );
			item.setAttribute( 'data-order', '' );
		} );

		// Add sorting attributes for the current selected column
		tableHeader.classList.add( 'smw-table-sort-' + order );
		tableHeader.setAttribute( 'data-order', order );

		tinysort(
			tableBody.querySelectorAll( 'div.smw-table-row' ),
			{
				selector: 'div:nth-child(' + ( tableHeaderIndex + 1 ) + ')',
				order: order
			}
		);
	} );

}( jQuery, mediaWiki ) );
