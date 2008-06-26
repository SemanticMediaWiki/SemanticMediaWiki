<?php

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
		parent::__construct('URIResolver', '', false);
	}

	function execute($query = '') {
		global $wgOut, $smwgIP;
		wfProfileIn('SpecialURIResolver::execute (SMW)');
		if ('' == $query) {
			if (stristr($_SERVER['HTTP_ACCEPT'], 'RDF')) {
				$wgOut->disable();
				header('HTTP/1.1 303 See Other');
				$s = Skin::makeSpecialUrl('ExportRDF', 'stats=1');
				header('Location: ' . $s);
			} else {
				$wgOut->addHTML(wfMsg('smw_uri_doc'));
			}
		} else {
			$wgOut->disable();
			$query = SMWExporter::decodeURI($query);
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
