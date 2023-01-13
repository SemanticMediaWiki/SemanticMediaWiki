<?php

use SMW\Exporter\ExporterFactory;

/**
 * This special page (Special:ExportRDF) for MediaWiki implements an OWL-export of semantic data,
 * gathered both from the annotations in articles, and from metadata already
 * present in the database.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWSpecialOWLExport extends SpecialPage {

	/// Export controller object to be used for serializing data
	protected $export_controller;

	public function __construct() {
		parent::__construct( 'ExportRDF' );
	}

	public function execute( $page ) {
		$this->setHeaders();
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( wfMessage( 'exportrdf' )->text() );

		// see if we can find something to export:
		$page = is_null( $page ) ? $wgRequest->getVal( 'page' ) : rawurldecode( $page );
		$pages = false;

		if ( !is_null( $page ) || $wgRequest->getCheck( 'page' ) ) {
			$page = is_null( $page ) ? $wgRequest->getCheck( 'text' ) : $page;

			if ( $page !== '' ) {
				$pages = [ $page ];
			}
		}

		if ( $pages === false && $wgRequest->getCheck( 'pages' ) ) {
			$pageBlob = $wgRequest->getText( 'pages' );

			if ( $pageBlob !== '' ) {
				$pages = explode( "\n", $wgRequest->getText( 'pages' ) );
			}
		}

		if ( $pages !== false ) {
			$this->exportPages( $pages );
			return;
		} else {
			$offset = $wgRequest->getVal( 'offset' );

			if ( isset( $offset ) ) {
				$this->startRDFExport();
				$this->export_controller->printPageList( $offset );
				return;
			} else {
				$stats = $wgRequest->getVal( 'stats' );

				if ( isset( $stats ) ) {
					$this->startRDFExport();
					$this->export_controller->printWikiInfo();
					return;
				}
			}
		}

		// Nothing exported yet; show user interface:
		$this->showForm();
	}

	/**
	 * Create the HTML user interface for this special page.
	 */
	protected function showForm() {
		global $wgOut, $smwgAllowRecursiveExport, $smwgExportBacklinks, $smwgExportAll;

		$user = $this->getUser();

		$html = '<form name="tripleSearch" action="" method="POST">' . "\n" .
					'<p>' . wfMessage( 'smw_exportrdf_docu' )->text() . "</p>\n" .
					'<input type="hidden" name="postform" value="1"/>' . "\n" .
					'<textarea name="pages" cols="40" rows="10"></textarea><br />' . "\n";

		if ( $user->isAllowed( 'delete' ) || $smwgAllowRecursiveExport ) {
			$html .= '<input type="checkbox" name="recursive" value="1" id="rec">&#160;<label for="rec">' . wfMessage( 'smw_exportrdf_recursive' )->text() . '</label></input><br />' . "\n";
		}

		if ( $user->isAllowed( 'delete' ) || $smwgExportBacklinks ) {
			$html .= '<input type="checkbox" name="backlinks" value="1" default="true" id="bl">&#160;<label for="bl">' . wfMessage( 'smw_exportrdf_backlinks' )->text() . '</label></input><br />' . "\n";
		}

		if ( $user->isAllowed( 'delete' ) || $smwgExportAll ) {
			$html .= '<br />';
			$html .= '<input type="text" name="date" value="' . date( DATE_W3C, mktime( 0, 0, 0, 1, 1, 2000 ) ) . '" id="date">&#160;<label for="ea">' . wfMessage( 'smw_exportrdf_lastdate' )->text() . '</label></input><br />' . "\n";
		}

		$html .= '<br /><input type="submit"  value="' . wfMessage( 'smw_exportrdf_submit' )->text() . "\"/>\n</form>";

		$wgOut->addHTML( $html );
	}

	/**
	 * Prepare $wgOut for printing non-HTML data.
	 */
	protected function startRDFExport() {
		global $wgOut, $wgRequest;

		$exporterFactory = new ExporterFactory();

		$syntax = $wgRequest->getText( 'syntax' );

		if ( $syntax === '' ) {
			$syntax = $wgRequest->getVal( 'syntax' );
		}

		$wgOut->disable();
		ob_start();

		if ( $syntax == 'turtle' ) {
			$mimetype = 'application/x-turtle'; // may change to 'text/turtle' at some time, watch Turtle development
			$serializer = $exporterFactory->newTurtleSerializer();
		} else { // rdfxml as default
			// Only use rdf+xml mimetype if explicitly requested (browsers do
			// not support it by default).
			// We do not add this parameter to RDF links within the export
			// though; it is only meant to help some tools to see that HTML
			// included resources are RDF (from there on they should be fine).
			$mimetype = ( $wgRequest->getVal( 'xmlmime' ) == 'rdf' ) ? 'application/rdf+xml' : 'application/xml';
			$serializer = $exporterFactory->newRDFXMLSerializer();
		}

		header( "Content-type: $mimetype; charset=UTF-8" );

		$this->export_controller = $exporterFactory->newExportController( $serializer );
	}

	/**
	 * Export the given pages to RDF.
	 * @param array $pages containing the string names of pages to be exported
	 */
	protected function exportPages( $pages ) {
		global $wgRequest, $smwgExportBacklinks, $smwgAllowRecursiveExport;

		$user = $this->getUser();

		// Effect: assume "no" from missing parameters generated by checkboxes.
		$postform = $wgRequest->getText( 'postform' ) == 1;

		$recursive = 0;  // default, no recursion
		$rec = $wgRequest->getText( 'recursive' );

		if ( $rec === '' ) {
			$rec = $wgRequest->getVal( 'recursive' );
		}

		if ( ( $rec == '1' ) && ( $smwgAllowRecursiveExport || $user->isAllowed( 'delete' ) ) ) {
			$recursive = 1; // users may be allowed to switch it on
		}

		$backlinks = $smwgExportBacklinks; // default
		$bl = $wgRequest->getText( 'backlinks' );

		if ( $bl === '' ) {
			// TODO: wtf? this does not make a lot of sense...
			$bl = $wgRequest->getVal( 'backlinks' );
		}

		if ( ( $bl == '1' ) && ( $user->isAllowed( 'delete' ) ) ) {
			$backlinks = true; // admins can always switch on backlinks
		} elseif ( ( $bl == '0' ) || ( '' == $bl && $postform ) ) {
			$backlinks = false; // everybody can explicitly switch off backlinks
		}

		$date = $wgRequest->getText( 'date' );
		if ( $date === '' ) {
			$date = $wgRequest->getVal( 'date', '' );
		}

		if ( $date !== '' ) {
			$timeint = strtotime( $date );
			$stamp = date( "YmdHis", $timeint );
			$date = $stamp;
		}

		// If it is a redirect then we don't want to generate triples other than
		// the redirect target information
		if ( isset( $pages[0] ) && ( $title = Title::newFromText( $pages[0] ) ) !== null && $title->isRedirect() ) {
			$backlinks = false;
		}

		$this->startRDFExport();
		$this->export_controller->enableBacklinks( $backlinks );
		$this->export_controller->printPages( $pages, $recursive, $date );
	}

	protected function getGroupName() {
		return 'smw_group';
	}
}
