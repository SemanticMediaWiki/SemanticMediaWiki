/*!
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */

/* global jQuery, mediaWiki, mw */
( function ( $, mw ) {

	'use strict';

	var dataTable = {

		/**
		 * Adds the initial sort/order from the #ask request that is available as
		 * `data-column-sort` attribute with something like:
		 *
		 * {
		 *  "list":["","Foo","Bar"]
		 *  "sort":["Foo"],
		 *  "order":["asc"]
		 * }
		 *
		 * on
		 *
		 * {{#ask: ...
		 *  |?Foo
		 *  |?Bar
		 *  |sort=Foo
		 *  |order=asc
		 *  ...
		 * }}
		 *
		 * @since 3.0
		 *
		 * @private
		 * @static
		 *
		 * @param {Object} context
		 */
		initColumnSort: function ( context ) {

			var column = context.data( 'column-sort' );
			var order = [];

			// In case of a transposed table, don't try to match a column or its order
			if ( column === undefined || !column.hasOwnProperty( 'sort' ) || column.sort.length === 0 || context.attr( 'data-transpose' ) ) {
				return;
			};

			// https://datatables.net/reference/api/order()
			// [1, 'asc'], [2, 'desc']
			$.map( column.sort, function( val, i ) {
				if ( val === '' ) {
					return;
				};
				order.push( [
					$.inArray( val, column.list ), // Find matchable index from the list
					column.order[i] === undefined ? 'desc' : column.order[i]
				] );
			} );

			if ( order.length > 0 ) {
				context.data( 'order', order );
			};
		},

		/**
		 * @since 3.0
		 *
		 * @private
		 * @static
		 *
		 * @param {Object} context
		 */
		addHeader: function ( context ) {

			// Copy the thead to a position the DataTable plug-in can transform
			// and display
			if ( context.find( 'thead' ).length === 0 ) {
				var head = context.find( 'tbody tr' );
				context.prepend( '<thead>' + head.html() + '</thead>' );
				head.eq(0).remove();

				// In case of a transposed, turn any td into a th
				context.find( 'thead td' ).wrapInner( '<th />' ).contents().unwrap();
			}

			// Ensure that any link in the header stops the propagation of the
			// click sorting event
			context.find( 'thead tr a' ).on( 'click.sorting', function ( event ) {
				event.stopPropagation();
			} );
		},

		/**
		 * @since 3.0
		 *
		 * @private
		 * @static
		 *
		 * @param {Object} context
		 */
		addFooter: function ( context ) {

			// As a transposed table, move the footer column to the bottom
			// and remove any footer-cell from the table matrix to
			// ensure a proper formatted table
			if ( context.data( 'transpose' ) === 1 && context.find( 'tbody .sortbottom' ).length === 1 ) {
				var footer = context.find( 'tbody .sortbottom' );
				context.append( '<tfoot><tr><td colspan=' + footer.index() + '>' + footer.html() + '</td></tr></tfoot>' );
				footer.eq(0).remove();

				// Remove remaining footer cells to avoid an uneven table
				context.find( 'tbody .footer-cell' ).each( function() {
					$( this ).remove();
				} );
			};

			// Copy the tbody to a position the DataTable plug-in can transform
			// and display
			if ( context.find( 'tbody .smwfooter' ).length == 1 ) {
				var footer = context.find( 'tbody .smwfooter' );
				context.append( '<tfoot>' + footer.html() + '</tfoot>' );
				footer.eq(0).remove();
			}
		},

		/**
		 * @since 3.0
		 *
		 * @param {Object} context
		 */
		attach: function ( context ) {

			var self = this;
			context.show();

			// Remove any class that may interfere due to some external JS or CSS
			context.removeClass( 'jquery-tablesorter' );
			context.removeClass( 'sortable' );
			context.removeClass( 'is-disabled' );
			context.removeClass( 'wikitable' );

			// DataTables default display class
			context.addClass( 'display' );

			mw.loader.using( 'onoi.dataTables' ).done( function () {

				self.initColumnSort( context );

				// MediaWiki table output is missing some standard formatting hence
				// add a footer and header
				self.addFooter( context );
				self.addHeader( context );

				// https://datatables.net/manual/tech-notes/3
				// Ensure the object initialization only happens once
				if ( $.fn.dataTable.isDataTable( context ) ) {
					return;
				}

				var table = context.DataTable( {
					searchHighlight: true,
					"language": {
						"sProcessing": mw.msg( 'smw-format-datatable-processing' ),
						"sLengthMenu": mw.msg( 'smw-format-datatable-lengthmenu' ),
						"sZeroRecords": mw.msg( 'smw-format-datatable-zerorecords' ),
						"sEmptyTable": mw.msg( 'smw-format-datatable-emptytable' ),
						"sInfo": mw.msg( 'smw-format-datatable-info' ),
						"sInfoEmpty": mw.msg( 'smw-format-datatable-infoempty' ),
						"sInfoFiltered": mw.msg( 'smw-format-datatable-infofiltered' ),
						"sSearch": mw.msg( 'smw-format-datatable-search' ),
						"sInfoThousands": mw.msg( 'smw-format-datatable-infothousands' ),
						"sLoadingRecords": mw.msg( 'smw-format-datatable-loadingrecords' ),
						"oPaginate": {
							"sFirst": mw.msg( 'smw-format-datatable-first' ),
							"sLast": mw.msg( 'smw-format-datatable-last' ),
							"sNext": mw.msg( 'smw-format-datatable-next' ),
							"sPrevious": mw.msg( 'smw-format-datatable-previous' )
						},
						"oAria": {
							"sSortAscending": mw.msg( 'smw-format-datatable-sortascending' ),
							"sSortDescending": mw.msg( 'smw-format-datatable-sortdescending' )
						}
					}
				} );

				// Remove accented characters from the search input
				context.parent().find( 'input' ).on( "keyup", function () {
					table.search(
						$.fn.DataTable.ext.type.search.string( $.trim( this.value ) )
					).draw();
				} );

				mw.hook( 'smw.tooltip' ).fire( context );
			} );
		},

		/**
		 * @since 3.0
		 *
		 * @private
		 * @static
		 *
		 * @param {Object} context
		 */
		init: function ( context ) {
			context.removeClass( 'smw-loading-image-dots' );
			context.find( '.smw-datatable' ).removeClass( 'smw-loading-image-dots' );
			this.attach( context.find( '.datatable' ) );
		}
	};

	$( document ).ready( function() {

		$( '.smw-datatable' ).each( function() {
			dataTable.init( $( this ) );
		} );

		// Listen to the smw.deferred.query event
		mw.hook( 'smw.deferred.query' ).add( function( context ) {
			dataTable.init( context );
		} );
	} );

}( jQuery, mediaWiki ) );
