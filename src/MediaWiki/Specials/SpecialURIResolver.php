<?php

namespace SMW\MediaWiki\Specials;

use SMW\Exporter\Escaper;
use SpecialPage;
use Title;

/**
 * Resolve (redirect) pretty URIs (or "short URIs") to the equivalent full MediaWiki
 * representation.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Denny Vrandecic
 */
class SpecialURIResolver extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'URIResolver', '', false );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string $query string
	 */
	public function execute( $query ) {
		$out = $this->getOutput();

		// #2344, It is believed that when no HTTP_ACCEPT is available then a
		// request came from a "defect" mobile device without a correct accept
		// header
		if ( !isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			$_SERVER['HTTP_ACCEPT'] = '';
		}

		if ( $query === null || trim( $query ) === '' ) {
			if ( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' ) ) {
				$out->redirect( SpecialPage::getTitleFor( 'ExportRDF' )->getFullURL( [ 'stats' => '1' ] ), '303' );
			} else {
				$this->setHeaders();
				$out->addHTML(
					'<p>' .
						wfMessage( 'smw_uri_doc', 'https://www.w3.org/2001/tag/issues.html#httpRange-14' )->parse() .
					'</p>'
				);
			}
		} else {
			$query = Escaper::decodeUri( $query );
			$query = str_replace( '_', '%20', $query );
			$query = urldecode( $query );
			$title = Title::newFromText( $query );

			// In case the title doesn't exist throw an error page
			if ( $title === null ) {
				$out->showErrorPage( 'badtitle', 'badtitletext' );
			} elseif ( stristr( $_SERVER['HTTP_ACCEPT'], 'RDF' ) ) {
				$out->redirect(
					SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getFullURL( [ 'xmlmime' => 'rdf' ] )
				);
			} else {
				$out->redirect( $title->getFullURL(), '303' );
			}
		}
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

}
