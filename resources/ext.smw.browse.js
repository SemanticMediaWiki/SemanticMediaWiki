/**
 * JavaScript for the Semantic MediaWiki extension.
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author Devayon Das
 */

(function( $, mw ) {

	$( document ).ready( function() {

		$( '#page_input_box' ).autocomplete( {
			minLength: 3,
			source: function( request, response ) {
				jQuery.getJSON(
					mw.config.get( 'wgScriptPath' ) + '/api.php',
					{
						'action': 'opensearch',
						'limit': 10,
						'namespace': 0,
						'format': 'json',
						'search': request.term
					},
					function( data ){
						response( data[1] );
					}
				);
			}
		} );

	} );

})( jQuery, mediaWiki );