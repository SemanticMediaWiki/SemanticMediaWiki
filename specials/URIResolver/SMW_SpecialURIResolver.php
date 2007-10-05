<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
include_once($IP . '/includes/SpecialPage.php');

/**
 * @author Denny Vrandecic
 *
 * This special page solves the URI crisis
 * without the need of changing code deep in
 * MediaWiki were no hook has ever seen light.
 *
 * @note AUTOLOAD
 */
class SMWURIResolver extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		smwfInitUserMessages();
		parent::__construct('URIResolver', '', false);
	}

	function execute($query = '') {
		global $wgOut, $smwgIP;
		wfProfileIn('SpecialURIResolver::execute (SMW)');
		if ('' == $query) {
			$wgOut->addHTML(wfMsg('smw_uri_doc'));
		} else {
			/// TODO: the next (large) include is used for just a single function, I think -- mak
			require_once( $smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php' );
			$wgOut->disable();

			$query = ExportRDF::makeURIfromXMLExportId($query);
			$query = str_replace( "_", "%20", $query );
			$query = urldecode($query);
			$title = Title::newFromText($query);

			header('HTTP/1.1 303 See Other');
			if (stristr($_SERVER['HTTP_ACCEPT'], 'RDF')) {
				$s = Skin::makeSpecialUrlSubpage('ExportRDF', $title->getPrefixedURL(), 'xmlmime=rdf');
				header('Location: ' . $s);
			} else {
				$t = $title->getFullURL();
				header('Location: ' . $t);
			}
		}
		wfProfileOut('SpecialURIResolver::execute (SMW)');
	}
}
