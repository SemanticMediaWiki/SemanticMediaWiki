<?php

use SMW\Exporter\Escaper;

/**
 * This special page solves the URI crisis
 * without the need of changing code deep in
 * MediaWiki were no hook has ever seen light.
 *
 * @ingroup SpecialPage
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Denny Vrandecic
 */
class SMWURIResolver extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'URIResolver', '', false );
	}

	function execute( $query ) {
		global $wgOut;

		// #2344, It is believed that when no HTTP_ACCEPT is available then a
		// request came from a "defect" mobile device without a correct accept
		// header
		if ( !isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			$_SERVER['HTTP_ACCEPT'] = '';
		}

		if ( is_null( $query ) || trim( $query ) === '' ) {
			if ( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' ) ) {
				$wgOut->redirect( SpecialPage::getTitleFor( 'ExportRDF' )->getFullURL( array( 'stats' => '1' ) ), '303' );
			} else {
				$this->setHeaders();
				$wgOut->addHTML(
					'<p>' .
						wfMessage( 'smw_uri_doc', 'http://www.w3.org/2001/tag/issues.html#httpRange-14' )->parse() .
					'</p>'
				);
			}
		} else {
			$query = Escaper::decodeUri( $query );
			$query = str_replace( '_', '%20', $query );
			$query = urldecode( $query );
			$title = Title::newFromText( $query );

			// In case the title doesn't exists throw an error page
			if ( $title === null ) {
				$wgOut->showErrorPage( 'badtitle', 'badtitletext' );
			} else {
				$wgOut->redirect( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' )
					? SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getFullURL( array( 'xmlmime' => 'rdf' ) )
					: $title->getFullURL(), '303' );
			}
		}

	}
}
