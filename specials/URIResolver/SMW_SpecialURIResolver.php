<?php
/**
 * @author Denny Vrandecic
 *
 * This special page solves with the URI crisis
 * without the need of changing code deep in
 * MediaWiki were no hook has ever seen light.
 */

if (!defined('MEDIAWIKI')) die();

class SMW_URIResolver {

	static function execute($query = '') {
		global $wgOut, $smwgIP;
		require_once( $smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php' );
		if ('' == $query) {
			$wgOut->addHTML(wfMsg('smw_uri_doc'));
		} else {
			$wgOut->disable();

			$query = ExportRDF::makeURIfromXMLExportId($query);
			$query = str_replace( "_", "%20", $query );
			$query = urldecode($query);
			$title = Title::newFromText($query);
			$t = $title->getFullURL();

			$rdftitle = Title::newFromText('ExportRDF', NS_SPECIAL);
			$s = $rdftitle->getFullURL() . "/" . $title->getPrefixedURL();

			header('HTTP/1.1 303 See Other');
			if (stristr($_SERVER['HTTP_ACCEPT'], 'RDF'))
				header('Location: ' . $s);
			else
				header('Location: ' . $t);
		}
	}
}
