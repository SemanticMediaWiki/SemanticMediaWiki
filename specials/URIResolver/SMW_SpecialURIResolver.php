<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

/**
 * @author Denny Vrandecic
 *
 * This special page solves the URI crisis
 * without the need of changing code deep in
 * MediaWiki were no hook has ever seen light.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWURIResolver extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'URIResolver', '', false );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	function execute( $query ) {
		global $wgOut, $smwgIP;
		
		wfProfileIn( 'SpecialURIResolver::execute (SMW)' );
		
		if ( $query == '' ) {
			if ( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' ) ) {
				$wgOut->redirect( SpecialPage::getTitleFor( 'ExportRDF' )->getFullURL( 'stats=1' ), '303' );
			} else {
				$this->setHeaders();
				$wgOut->addHTML( '<p>' . wfMsg( 'smw_uri_doc' ) . "</p>" );
			}
		} else {
			$query = SMWExporter::decodeURI( $query );
			$query = str_replace( "_", "%20", $query );
			$query = urldecode( $query );
			$title = Title::newFromText( $query );

			$wgOut->redirect( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' )
				? SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getFullURL( 'xmlmime=rdf' )
				: $title->getFullURL(), '303' );
		}
		
		wfProfileOut( 'SpecialURIResolver::execute (SMW)' );
	}
}
