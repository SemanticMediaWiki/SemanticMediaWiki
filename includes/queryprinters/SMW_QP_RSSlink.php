<?php
/**
 * Print links to RSS feeds for query results.
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer for creating a link to RSS feeds.
 *
 * @author Denny Vrandecic
 * @author Markus Krötzsch
 * 
 * @ingroup SMWQuery
 */
class SMWRSSResultPrinter extends SMWResultPrinter {
	
	protected $m_title = '';
	protected $m_description = '';

	/**
	 * @see SMWResultPrinter::handleParameters
	 * 
	 * @since 1.7
	 * 
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );
		
		$this->m_title = trim( $params['title'] );
		$this->m_description = trim( $params['description'] );
	}
	
	public function getMimeType( $res ) {
		// or is rdf+xml better? Might be confused in either case (with RSS2.0 or RDF)
		return 'application/rss+xml';
	}

	public function getQueryMode( $context ) {
		return $context == SMWQueryProcessor::SPECIAL_PAGE ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		return wfMsg( 'smw_printername_rss' );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $smwgIQRunningNumber, $wgSitename, $wgServer, $smwgRSSEnabled, $wgRequest;
		$result = '';
		if ( $outputmode == SMW_OUTPUT_FILE ) { // make RSS feed
			if ( !$smwgRSSEnabled ) return '';
			if ( $this->m_title === '' ) {
				$this->m_title = $wgSitename;
			}
			if ( $this->m_description === '' ) {
				$this->m_description = wfMsg( 'smw_rss_description', $wgSitename );
			}

			// cast printouts into "items"
			$items = array();
			$row = $res->getNext();
			while ( $row !== false ) {
				$creators = array();
				$dates = array();
				$wikipage = $row[0]->getNextDataValue(); // get the object
				foreach ( $row as $field ) {
					// for now we ignore everything but creator and date, later we may
					// add more things like geolocs, categories, and even a generic
					// mechanism to add whatever you want :)
					$req = $field->getPrintRequest();
					if ( strtolower( $req->getLabel() ) == 'creator' ) {
						while ( $entry = $field->getNextDataValue() ) {
							$creators[] = $entry->getShortWikiText();
						}
					} elseif ( ( strtolower( $req->getLabel() ) == 'date' ) && ( $req->getTypeID() == '_dat' ) ) {
						while ( $entry = $field->getNextDataValue() ) {
							$dates[] = $entry->getXMLSchemaDate();
						}
					}
				}
				if ( $wikipage instanceof SMWWikiPageValue ) { // this should rarely fail, but better be carful
					///TODO: It would be more elegant to have type chekcs initially
					$items[] = new SMWRSSItem( $wikipage->getTitle(), $creators, $dates );
				}
				$row = $res->getNext();
			}

			$result .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$result .= "<rdf:RDF\n";
			$result .= "\txmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
			$result .= "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n";
			$result .= "\txmlns:admin=\"http://webns.net/mvcb/\"\n";
			$result .= "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
			$result .= "\txmlns=\"http://purl.org/rss/1.0/\">\n";
			$result .= "\t<channel rdf:about=\"" . str_replace( '&', '&amp;', $wgRequest->getFullRequestURL() ) . "\">\n";
			$result .= "\t\t<admin:generatorAgent rdf:resource=\"http://semantic-mediawiki.org/wiki/Special:URIResolver/Semantic_MediaWiki\"/>\n";
			$result .= "\t\t<title>" . smwfXMLContentEncode( $this->m_title ) . "</title>\n";
			$result .= "\t\t<link>$wgServer</link>\n";
			$result .= "\t\t<description>" . smwfXMLContentEncode( $this->m_description ) . "</description>\n";
			if ( count( $items ) > 0 ) {
				$result .= "\t\t<items>\n";
				$result .= "\t\t\t<rdf:Seq>\n";
				foreach ( $items as $item ) {
					$result .= "\t\t\t\t<rdf:li rdf:resource=\"" . $item->uri() . "\"/>\n";
				}
				$result .= "\t\t\t</rdf:Seq>\n";
				$result .= "\t\t</items>\n";
			}
			$result .= "\t</channel>\n";
			foreach ( $items as $item ) {
				$result .= $item->text();
			}
			$result .= '</rdf:RDF>';
		} else { // just make link to feed
			if ( $this->getSearchLabel( $outputmode ) ) {
				$label = $this->getSearchLabel( $outputmode );
			} else {
				$label = wfMsgForContent( 'smw_rss_link' );
			}
			$link = $res->getQueryLink( $label );
			$link->setParameter( 'rss', 'format' );
			if ( $this->m_title !== '' ) {
				$link->setParameter( $this->m_title, 'title' );
			}
			if ( $this->m_description !== '' ) {
				$link->setParameter( $this->m_description, 'description' );
			}
			if ( array_key_exists( 'limit', $this->m_params ) ) {
				$link->setParameter( $this->m_params['limit'], 'limit' );
			} else { // use a reasonable deafult limit (10 is suggested by RSS)
				$link->setParameter( 10, 'limit' );
			}

			foreach ( $res->getPrintRequests() as $printout ) { // overwrite given "sort" parameter with printout of label "date"
				if ( ( $printout->getMode() == SMWPrintRequest::PRINT_PROP ) && ( strtolower( $printout->getLabel() ) == "date" ) && ( $printout->getTypeID() == "_dat" ) ) {
					$link->setParameter( $printout->getData()->getWikiValue(), 'sort' );
				}
			}

			$result .= $link->getText( $outputmode, $this->mLinker );
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
			SMWOutputs::requireHeadItem( 'rss' . $smwgIQRunningNumber, '<link rel="alternate" type="application/rss+xml" title="' . $this->m_title . '" href="' . $link->getURL() . '" />' );
		}

		return $result;
	}

	public function getParameters() {
		$params = array_merge( parent::getParameters(), $this->exportFormatParameters() );
		
		$params['title'] = new Parameter( 'title' );
		$params['title']->setMessage( 'smw_paramdesc_rsstitle' );
		$params['title']->setDefault( '' );
		
		$params['description'] = new Parameter( 'description' );
		$params['description']->setMessage( 'smw_paramdesc_rssdescription' );
		$params['description']->setDefault( '' );
		
		return $params;
	}

}


/**
 * Represents a single entry, or item, in an RSS feed. Useful since those items are iterated more
 * than once when serialising RSS.
 * @todo This code still needs cleanup, it's a mess.
 * @ingroup SMWQuery
 */
class SMWRSSItem {

	private $uri;
	private $label;
	private $creator;
	private $date;
	private $articlename;
	private $title;

	/**
	 * Constructor for a single item in the feed. Requires the URI of the item.
	 */
	public function __construct( Title $t, $c, $d ) {
		$this->title = $t;
		$this->uri = $t->getFullURL();
		$this->label = $t->getText();
		$article = null;
		if ( count( $c ) == 0 ) {
			$article = new Article( $t );
			$this->creator = array();
			$this->creator[] = $article->getUserText();
		} else {
			$this->creator = $c;
		}
		$this->date = array();
		if ( count( $d ) == 0 ) {
			if ( is_null( $article ) ) {
				$article = new Article( $t );
			}
			$this->date[] = date( "c", strtotime( $article->getTimestamp() ) );
		} else {
			foreach ( $d as $date ) {
				$this->date[] = $date;
			}
		}

		// get content
		if ( $t->getNamespace() == NS_MAIN ) {
			$this->articlename = ':' . $t->getDBkey();
		} else {
			$this->articlename = $t->getPrefixedDBKey();
		}
	}

	/**
	 * Get function for the Item URI
	 */
	public function uri() {
		return $this->uri;
	}

	/**
	 * Creates the RSS output for the single item.
	 */
	public function text() {
		global $wgServer, $wgParser, $smwgShowFactbox, $smwgRSSWithPages;
		static $parser_options = null;
		$smwgShowFactbox = SMW_FACTBOX_HIDDEN; // just hide factbox; no need to restore this setting, I hope that nothing comes after FILE outputs

		$text  = "\t<item rdf:about=\"$this->uri\">\n";
		$text .= "\t\t<title>" . smwfXMLContentEncode( $this->label ) . "</title>\n";
		$text .= "\t\t<link>" . smwfXMLContentEncode( $this->uri ) . "</link>\n";
		foreach ( $this->date as $date )
			$text .= "\t\t<dc:date>$date</dc:date>\n";
		foreach ( $this->creator as $creator )
			$text .= "\t\t<dc:creator>" . smwfXMLContentEncode( $creator ) . "</dc:creator>\n";
		if ( $smwgRSSWithPages ) {
			$parser_options = new ParserOptions();
			$parser_options->setEditSection( false );  // embedded sections should not have edit links
			$parserOutput = $wgParser->parse( '{{' . $this->articlename . '}}', $this->title, $parser_options );
			$content = $parserOutput->getText();
			// Make absolute URLs out of the local ones:
			///TODO is there maybe a way in the parser options to make the URLs absolute?
			$content = str_replace( '<a href="/', '<a href="' . $wgServer . '/', $content );
			$text .= "\t\t<description>" . smwfXMLContentEncode( $content ) . "</description>\n";
			$text .= "\t\t<content:encoded  rdf:datatype=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral\"><![CDATA[$content]]></content:encoded>\n";
		}
		$text .= "\t</item>\n";
		return $text;
	}

}
