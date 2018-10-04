/*!
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * @since 3.0
	 * @class
	 */
	smw.ui = smw.ui || {};

	/**
	 * Class constructor
	 *
	 * @since 3.0
	 *
	 * @class
	 * @constructor
	 */
	smw.ui = function() {
		'use strict';
	};

	/* Public methods */

	smw.ui.prototype = {

		/**
		 * @since  3.0
		 */
		selectMenu: function( context, opts ) {

			var that = context;
			var val = that.prop( 'value' );
			var data = that.data( 'list' );

			that.selectMenu(
				smw.merge(
					{
						showField : 'desc',
						keyField : 'id',
						arrow : true,
						selectToCloseList: true,
						initSelected : val,
						search: false,
						title: that.prop( 'title' ),
						data : data,
					},
					opts
				)
			);
		}
	}

} )( jQuery, mediaWiki, semanticMediaWiki );
