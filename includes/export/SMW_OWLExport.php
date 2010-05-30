<?php

/**
 * File holding the OWLExport class for OWL export, used by SMWSpecialOWLExport (Special:ExportRDF page).
 *
 * @file SMW_OWLExport.php
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

/**
 * Small data object holding the bare essentials of one title.
 * Used to store processed and open pages for export.
 * 
 * @ingroup SMW
 */
class SMWSmallTitle {
	public $dbkey;
	public $namespace; // MW namespace constant
	public $modifier = ''; // e.g. a unit string

	public function getHash() {
		return $this->dbkey . ' ' . $this->namespace . ' ' . $this->modifier;
	}
}


/**
 * Class for encapsulating the methods for RDF export.
 * 
 * @ingroup SMW
 */
class SMWOWLExport {
	/**#@+
	 * @access private
	 */

	const MAX_CACHE_SIZE = 5000; // do not let cache arrays get larger than this
	const CACHE_BACKJUMP = 500;  // kill this many cached entries if limit is reached,
	                             // avoids too much array copying; <= MAX_CACHE_SIZE!

	/**
	 * An array that keeps track of the elements for which we still need to
	 * write auxilliary definitions.
	 */
	private $element_queue;

	/**
	 * An array that keeps track of the elements which have been exported already
	 */
	private $element_done;

	/**
	 * Date used to filter the export. If a page has not been changed since that
	 * date it will not be exported
	 */
	private $date;

	/**
	 * Array of additional namespaces (abbreviation => URI), flushed on
	 * closing the current namespace tag. Since we export RDF in a streamed
	 * way, it is not always possible to embed additional namespaces into
	 * the RDF-tag which might have been sent to the client already. But we
	 * wait with printing the current Description so that extra namespaces
	 * from this array can still be printed (note that you never know which
	 * extra namespaces you encounter during export).
	 */
	private $extra_namespaces;

	/**
	 * Array of namespaces that have been declared globally already. Contains
	 * entries of format 'namespace abbreviation' => true, assuming that the
	 * same abbreviation always refers to the same URI (i.e. you cannot import
	 * something as rdf:bla if you do not want rdf to be the standard
	 * namespace that is already given in every RDF export).
	 */
	private $global_namespaces;

	/**
	 * Unprinted XML is composed from the strings $pre_ns_buffer and $post_ns_buffer.
	 * The split between the two is such that one can append additional namespace
	 * declarations to $pre_ns_buffer so that they affect all current elements. The
	 * buffers are flushed during output in order to achieve "streaming" RDF export
	 * for larger files.
	 */
	private $pre_ns_buffer;

	/**
	 * See documentation for SMWOWLExport::pre_ns_buffer.
	 */
	private $post_ns_buffer;

	/**
	 * Boolean that is true as long as nothing was flushed yet. Indicates that
	 * extra namespaces can still become global.
	 */
	private $first_flush;

	/**
	 * Integer that counts down the number of objects we still process before
	 * doing the first flush. Aggregating some output before flushing is useful
	 * to get more namespaces global. Flushing will only happen if $delay_flush
	 * is 0.
	 */
	private $delay_flush;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->element_queue = array();
		$this->element_done = array();
		$this->date = '';
	}

	/**
	 * Sets a date as a filter. Any page that has not been changed since that date
	 * will not be exported. The date has to be a string in XML Schema format.
	 */
	public function setDate( $date ) {
		$timeint = strtotime( $date );
		$stamp = date( "YmdHis", $timeint );
		$this->date = $stamp;
	}

	/**
	 * This function prints all selected pages. The parameter $recursion determines
	 * how referenced ressources are treated:
	 * '0' : add brief declarations for each
	 * '1' : add full descriptions for each, thus beginning real recursion (and
	 *       probably retrieving the whole wiki ...)
	 * else: ignore them, though -1 might become a synonym for "export *all*" in the future
	 * The parameter $backlinks determines whether or not subjects of incoming
	 * properties are exported as well. Enables "browsable RDF."
	 */
	public function printPages( $pages, $recursion = 1, $backlinks = true ) {
		wfProfileIn( "RDF::PrintPages" );

		$linkCache =& LinkCache::singleton();
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->first_flush = true;
		$this->delay_flush = 10; // flush only after (fully) printing 11 objects
		$this->extra_namespaces = array();

		if ( count( $pages ) == 1 ) { // ensure that ontologies that are retrieved as linked data are not confused with their subject!
			$ontologyuri = SMWExporter::expandURI( '&export;' ) . '/' . urlencode( end( $pages ) );
		} else { // use empty URI, i.e. "location" as URI otherwise
			$ontologyuri = '';
		}
		
		$this->printHeader( $ontologyuri ); // also inits global namespaces

		wfProfileIn( "RDF::PrintPages::PrepareQueue" );
		
		// transform pages into queued export titles
		$cur_queue = array();
		
		foreach ( $pages as $page ) {
			$title = Title::newFromText( $page );
			
			if ( null === $title ) continue; // invalid title name given
			
			$st = new SMWSmallTitle();
			$st->dbkey = $title->getDBkey();
			$st->namespace = $title->getNamespace();
			
			$cur_queue[] = $st;
		}
		
		wfProfileOut( "RDF::PrintPages::PrepareQueue" );

		while ( count( $cur_queue ) > 0 ) {
			// first, print all selected pages
			foreach ( $cur_queue as $st ) {
				wfProfileIn( "RDF::PrintPages::PrintOne" );
				
				$this->printObject( $st, true, $backlinks );
				
				wfProfileOut( "RDF::PrintPages::PrintOne" );
				
				if ( $this->delay_flush > 0 ) $this->delay_flush--;
			}
			
			// prepare array for next iteration
			$cur_queue = array();
			
			if ( 1 == $recursion ) {
				$cur_queue = $this->element_queue + $cur_queue; // make sure the array is *dublicated* instead of copying its ref
				$this->element_queue = array();
			}
			
			$linkCache->clear();
		}

		// for pages not processed recursively, print at least basic declarations
		wfProfileIn( "RDF::PrintPages::Auxiliary" );
		$this->date = ''; // no date restriction for the rest!
		
		if ( !empty( $this->element_queue ) ) {
			if ( $this->pre_ns_buffer != '' ) {
				$this->post_ns_buffer .= "\t<!-- auxiliary definitions -->\n";
			} else {
				print "\t<!-- auxiliary definitions -->\n"; // just print this comment, so that later outputs still find the empty pre_ns_buffer!
			}
			
			while ( !empty( $this->element_queue ) ) {
				$st = array_pop( $this->element_queue );
				$this->printObject( $st, false, false );
			}
		}
		
		wfProfileOut( "RDF::PrintPages::Auxiliary" );
		
		$this->printFooter();
		$this->flushBuffers( true );
		
		wfProfileOut( "RDF::PrintPages" );
	}

	/**
	 * This function prints RDF for *all* pages within the wiki, and for all
	 * elements that are referred to in the exported RDF.
	 */
	public function printAll( $outfile, $ns_restriction = false, $delay, $delayeach ) {
		global $smwgNamespacesWithSemanticLinks;
		
		$linkCache =& LinkCache::singleton();

		$db = & wfGetDB( DB_MASTER );
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->first_flush = true;
		
		if ( $outfile === false ) {
			// $this->delay_flush = 10000; //flush only after (fully) printing 10001 objects,
			$this->delay_flush = - 1; // do not flush buffer at all
		} else {
			$file = fopen( $outfile, 'w' );
			
			if ( !$file ) {
				print "\nCannot open \"$outfile\" for writing.\n";
				return false;
			}
			
			$this->delay_flush = - 1; // never flush, we flush in another way
		}
		
		$this->extra_namespaces = array();
		$this->printHeader(); // also inits global namespaces

		$start = 1;
		$end = $db->selectField( 'page', 'max(page_id)', false, $outfile );

		$a_count = 0; $d_count = 0; // DEBUG

		$delaycount = $delayeach;
		
		for ( $id = $start; $id <= $end; $id++ ) {
			$title = Title::newFromID( $id );
			
			if ( ( $title === null ) || !smwfIsSemanticsProcessed( $title->getNamespace() ) ) continue;
			if ( !OWLExport::fitsNsRestriction( $ns_restriction, $title->getNamespace() ) ) continue;
			
			$st = new SMWSmallTitle();
			$st->dbkey = $title->getDBkey();
			$st->namespace = $title->getNamespace();
			
			$cur_queue = array( $st );
			$a_count++; // DEBUG
			$full_export = true;
			
			while ( count( $cur_queue ) > 0 ) {
				foreach ( $cur_queue as $st ) {
					wfProfileIn( "RDF::PrintAll::PrintOne" );
					$this->printObject( $st, $full_export, false );
					wfProfileOut( "RDF::PrintAll::PrintOne" );
				}
				
				$full_export = false; // make sure added dependencies do not pull more than needed
				// resolve dependencies that will otherwise not be printed
				$cur_queue = array();
				
				foreach ( $this->element_queue as $key => $staux ) {
					$taux = Title::makeTitle( $staux->namespace, $staux->dbkey );
					
					if ( !smwfIsSemanticsProcessed( $staux->namespace ) || ( $staux->modifier !== '' ) ||
					     !OWLExport::fitsNsRestriction( $ns_restriction, $staux->namespace ) ||
					     ( !$taux->exists() ) ) {
					// Note: we do not need to check the cache to guess if an element was already
					// printed. If so, it would not be included in the queue in the first place.
						$cur_queue[] = $staux;
 					//	$this->post_ns_buffer .= "<!-- Adding dependency '" . $staux->getHash() . "' -->"; //DEBUG
						$d_count++; // DEBUG
					} else {
						unset( $this->element_queue[$key] ); // carrying around the values we do not
						                                   // want to export now is a potential memory leak
					}
				}
				
				// sleep each $delaycount for $delay ms to be nice to the server
				if ( ( $delaycount-- < 0 ) && ( $delayeach != 0 ) ) {
					usleep( $delay );
					$delaycount = $delayeach;
				}
			}
			
			if ( $outfile !== false ) { // flush buffer
				fwrite( $file, $this->post_ns_buffer );
				$this->post_ns_buffer = '';
			}
			
			$linkCache->clear();
		}
		
		// DEBUG:
		$this->post_ns_buffer .= "<!-- Processed $a_count regular articles. -->\n";
		$this->post_ns_buffer .= "<!-- Processed $d_count added dependencies. -->\n";
		$this->post_ns_buffer .= "<!-- Final cache size was " . sizeof( $this->element_done ) . ". -->\n";

		$this->printFooter();

		if ( $outfile === false ) {
			$this->flushBuffers( true );
		} else { // prepend headers to file, there is no really efficient solution (`cat(1)`) for this it seems
			// print head:
			fclose( $file );
			
			foreach ( $this->extra_namespaces as $nsshort => $nsuri ) {
				 $this->pre_ns_buffer .= "\n\txmlns:$nsshort=\"$nsuri\"";
			}
			
			$full_export = file_get_contents( $outfile );
			$full_export = $this->pre_ns_buffer . $full_export . $this->post_ns_buffer;
			
			$file = fopen( $outfile, 'w' );
			fwrite( $file, $full_export );
			fclose( $file );
		}
	}

	/**
	 * Print basic definitions a list of pages ordered by their page id.
	 * Offset and limit refer to the count of existing pages, not to the
	 * page id.
	 */
	public function printPageList( $offset = 0, $limit = 30 ) {
		wfProfileIn( "RDF::PrintPageList" );

		$db = & wfGetDB( DB_MASTER );
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->first_flush = true;
		$this->delay_flush = 10; // flush only after (fully) printing 11 objects
		$this->extra_namespaces = array();
		$this->printHeader(); // also inits global namespaces
		$linkCache =& LinkCache::singleton();

		global $smwgNamespacesWithSemanticLinks;
		$query = '';
		
		foreach ( $smwgNamespacesWithSemanticLinks as $ns => $enabled ) {
			if ( $enabled ) {
				if ( $query != '' ) $query .= ' OR ';
				$query .= 'page_namespace = ' . $db->addQuotes( $ns );
			}
		}
		
		$res = $db->select( $db->tableName( 'page' ),
		                    'page_id,page_title,page_namespace', $query
		                    , 'SMW::RDF::PrintPageList', array( 'ORDER BY' => 'page_id ASC', 'OFFSET' => $offset, 'LIMIT' => $limit ) );
		$foundpages = false;
		
		while ( $row = $db->fetchObject( $res ) ) {
			$foundpages = true;
			// $t = Title::makeTitle($row->page_namespace, $row->page_title);
			// if ($t === null) continue;
			// $et = new SMWExportTitle($t, $this);
			$st = new SMWSmallTitle();
			
			$st->dbkey = $row->page_title;
			$st->namespace = $row->page_namespace;
			
			$this->printObject( $st, false, false );
			
			if ( $this->delay_flush > 0 ) $this->delay_flush--;
			
			$linkCache->clear();
		}
		if ( $foundpages ) { // add link to next result page
			if ( strpos( SMWExporter::expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
				$nexturl = SMWExporter::expandURI( '&export;?offset=' ) . ( $offset + $limit );
			} else {
				$nexturl = SMWExporter::expandURI( '&export;&amp;offset=' ) . ( $offset + $limit );
			}
			
			$this->post_ns_buffer .=
			    "\t<!-- Link to next set of results -->\n" .
			    "\t<owl:Thing rdf:about=\"$nexturl\">\n" .
			    "\t\t<rdfs:isDefinedBy rdf:resource=\"$nexturl\"/>\n" .
			    "\t</owl:Thing>\n";
		}
		
		$this->printFooter();
		$this->flushBuffers( true );

		wfProfileOut( "RDF::PrintPageList" );
	}


	/**
	 * Print basic information about this site.
	 */
	public function printWikiInfo() {
		wfProfileIn( "RDF::PrintWikiInfo" );
		
		global $wgSitename, $wgLanguageCode;

		$db = & wfGetDB( DB_MASTER );
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->extra_namespaces = array();
		$data = new SMWExpData( new SMWExpResource( '&wiki;#wiki' ) );

		$ed = new SMWExpData( SMWExporter::getSpecialElement( 'swivt', 'Wikisite' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdf', 'type' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( $wgSitename ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdfs', 'label' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( $wgSitename, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'siteName' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SMWExporter::expandURI( '&wikiurl;' ), null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'pagePrefix' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SMW_VERSION, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'smwVersion' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( $wgLanguageCode, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'langCode' ), $ed );

		// stats
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::pages(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'pageCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::articles(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'contentPageCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::images(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'mediaCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::edits(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'editCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::views(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'viewCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::users(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'userCount' ), $ed );
		
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::admins(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'adminCount' ), $ed );

		$mainpage = Title::newMainPage();
		
		if ( $mainpage !== null ) {
			$ed = new SMWExpData( new SMWExpResource( $mainpage->getFullURL() ) );
			$data->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'mainPage' ), $ed );
		}

		$this->printHeader(); // also inits global namespaces
		$this->printExpData( $data );
		
		if ( strpos( SMWExporter::expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
			$nexturl = SMWExporter::expandURI( '&export;?offset=0' );
		} else {
			$nexturl = SMWExporter::expandURI( '&export;&amp;offset=0' );
		}
		
		$this->post_ns_buffer .=
			    "\t<!-- Link to semantic page list -->\n" .
			    "\t<owl:Thing rdf:about=\"$nexturl\">\n" .
			    "\t\t<rdfs:isDefinedBy rdf:resource=\"$nexturl\"/>\n" .
			    "\t</owl:Thing>\n";
		
		$this->printFooter();
		$this->flushBuffers( true );

		wfProfileOut( "RDF::PrintWikiInfo" );
	}

	/* Functions for exporting RDF */

	protected function printHeader( $ontologyuri = '' ) {
		global $wgContLang;

		$this->pre_ns_buffer .=
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<!DOCTYPE rdf:RDF[\n" .
			"\t<!ENTITY rdf '"   . SMWExporter::expandURI( '&rdf;' )   .  "'>\n" .
			"\t<!ENTITY rdfs '"  . SMWExporter::expandURI( '&rdfs;' )  .  "'>\n" .
			"\t<!ENTITY owl '"   . SMWExporter::expandURI( '&owl;' )   .  "'>\n" .
			"\t<!ENTITY swivt '" . SMWExporter::expandURI( '&swivt;' ) .  "'>\n" .
			// A note on "wiki": this namespace is crucial as a fallback when it would be illegal to start e.g. with a number. In this case, one can always use wiki:... followed by "_" and possibly some namespace, since _ is legal as a first character.
			"\t<!ENTITY wiki '"  . SMWExporter::expandURI( '&wiki;' ) .  "'>\n" .
			"\t<!ENTITY property '" . SMWExporter::expandURI( '&property;' ) .  "'>\n" .
			"\t<!ENTITY wikiurl '" . SMWExporter::expandURI( '&wikiurl;' ) .  "'>\n" .
			"]>\n\n" .
			"<rdf:RDF\n" .
			"\txmlns:rdf=\"&rdf;\"\n" .
			"\txmlns:rdfs=\"&rdfs;\"\n" .
			"\txmlns:owl =\"&owl;\"\n" .
			"\txmlns:swivt=\"&swivt;\"\n" .
			"\txmlns:wiki=\"&wiki;\"\n" .
			"\txmlns:property=\"&property;\"";
		$this->global_namespaces = array( 'rdf' => true, 'rdfs' => true, 'owl' => true, 'swivt' => true, 'wiki' => true, 'property' => true );

		$this->post_ns_buffer .=
			">\n\t<!-- Ontology header -->\n" .
			"\t<owl:Ontology rdf:about=\"$ontologyuri\">\n" .
			"\t\t<swivt:creationDate rdf:datatype=\"http://www.w3.org/2001/XMLSchema#dateTime\">" . date( DATE_W3C ) . "</swivt:creationDate>\n" .
			"\t\t<owl:imports rdf:resource=\"http://semantic-mediawiki.org/swivt/1.0\" />\n" .
			"\t</owl:Ontology>\n" .
			"\t<!-- exported page data -->\n";
	}

	/**
	 * Prints the footer.
	 */
	protected function printFooter() {
		$this->post_ns_buffer .= "\t<!-- Created by Semantic MediaWiki, http://semantic-mediawiki.org/ -->\n";
		$this->post_ns_buffer .= '</rdf:RDF>';
	}

	/**
	 * Serialise the given semantic data.
	 */
	protected function printExpData( /*SMWExpData*/ $data, $indent = '' ) {
		$type = $data->extractMainType()->getQName();
		
		if ( '' == $this->pre_ns_buffer ) { // start new ns block
			$this->pre_ns_buffer .= "\t$indent<$type";
		} else {
			$this->post_ns_buffer .= "\t$indent<$type";
		}
		
		if ( ( $data->getSubject() instanceof SMWExpLiteral ) || ( $data->getSubject() instanceof SMWExpResource ) ) {
			 $this->post_ns_buffer .= ' rdf:about="' . $data->getSubject()->getName() . '"';
		} // else: blank node
		
		if ( count( $data->getProperties() ) == 0 ) {
			$this->post_ns_buffer .= " />\n";
		} else {
			$this->post_ns_buffer .= ">\n";
			
			foreach ( $data->getProperties() as $property ) {
				$this->queueElement( $property );
				
				foreach ( $data->getValues( $property ) as $value ) {
					$this->post_ns_buffer .= "\t\t$indent<" . $property->getQName();
					$this->addExtraNamespace( $property->getNamespaceID(), $property->getNamespace() );
					$object = $value->getSubject();
					
					if ( $object instanceof SMWExpLiteral ) {
						if ( $object->getDatatype() != '' ) {
							$this->post_ns_buffer .= ' rdf:datatype="' . $object->getDatatype() . '"';
						}
						
						$this->post_ns_buffer .= '>' .
							str_replace( array( '&', '>', '<' ), array( '&amp;', '&gt;', '&lt;' ), $object->getName() ) .
							'</' . $property->getQName() . ">\n";
					} else { // bnode or resource, may have subdescriptions
						$collection = $value->getCollection();
						
						if ( $collection != false ) {
							$this->post_ns_buffer .= " rdf:parseType=\"Collection\">\n";
							
							foreach ( $collection as $subvalue ) {
								$this->printExpData( $subvalue, $indent . "\t\t" );
							}
							
							$this->post_ns_buffer .= "\t\t$indent</" . $property->getQName() . ">\n";
						} elseif ( count( $value->getProperties() ) > 0 ) {
							$this->post_ns_buffer .= ">\n";
							$this->printExpData( $value, $indent . "\t\t" );
							$this->post_ns_buffer .= "\t\t$indent</" . $property->getQName() . ">\n";
						} else {
							if ( $object instanceof SMWExpResource ) {
								$this->post_ns_buffer .= ' rdf:resource="' . $object->getName() . '"';
								$this->queueElement( $object ); // queue only non-explicated resources
							}
							
							$this->post_ns_buffer .= "/>\n";
						}
					}
				}
			}
			
			$this->post_ns_buffer .= "\t$indent</" . $type . ">\n";
		}
		
		$this->flushBuffers();
	}

	/**
	 * Print the triples associated to a specific page, and references those needed.
	 * They get printed in the printFooter-function.
	 *
	 * @param $st The SMWSmallTitle wrapping the page to be exported
	 * @param $fullexport Boolean to define whether all the data for the page should
	 * be exported, or whether just a definition of the given title.
	 * @param $backlinks Boolean specifying if properties linking to the exported title
	 * should be included.
	 */
	protected function printObject( /*SMWSmallTitle*/ $st, $fullexport = true, $backlinks = false ) {
		global $smwgMW_1_14;
		
		if ( array_key_exists( $st->getHash(), $this->element_done ) ) return; // do not export twice

		$value = SMWWikiPageValue::makePage( $st->dbkey, $st->namespace );
		
		if ( $this->date !== '' ) { // check date restriction if given
			$rev = $smwgMW_1_14 ? Revision::getTimeStampFromID( $value->getTitle(), $value->getTitle()->getLatestRevID() ):Revision::getTimeStampFromID( $value->getTitle()->getLatestRevID() );
			if ( $rev < $this->date ) return;
		}

		$data = SMWExporter::makeExportData( smwfGetStore()->getSemanticData( $value, $fullexport ? false:array( '__spu', '__typ', '__imp' ) ), $st->modifier );
		$this->printExpData( $data ); // serialise
		
		// let other extensions add additional RDF data for this page
		$additionalDataArray = array();
		
		wfRunHooks( 'smwAddToRDFExport', array( $value->getTitle(), &$additionalDataArray ) );
		
		foreach ( $additionalDataArray as $additionalData ) {
			$this->printExpData( $additionalData ); // serialise
		}
		
		$this->markAsDone( $st );

		// possibly add backlinks
		if ( ( $fullexport ) && ( $backlinks ) ) {
			wfProfileIn( "RDF::PrintPages::GetBacklinks" );
			$inRels = smwfGetStore()->getInProperties( $value );
			
			foreach ( $inRels as $inRel ) {
				$inSubs = smwfGetStore()->getPropertySubjects( $inRel, $value );
				
				foreach ( $inSubs as $inSub ) {
					$stb = new SMWSmallTitle();
					$stb->dbkey = $inSub->getDBkey();
					$stb->namespace = $inSub->getNamespace();
					
					if ( !array_key_exists( $stb->getHash(), $this->element_done ) ) {
						$semdata = smwfGetStore()->getSemanticData( $inSub, array( '__spu', '__typ', '__imp' ) );
						$semdata->addPropertyObjectValue( $inRel, $value );
						$data = SMWExporter::makeExportData( $semdata );
						$this->printExpData( $data );
					}
				}
			}
			
			if ( NS_CATEGORY === $value->getNamespace() ) { // also print elements of categories
				$options = new SMWRequestOptions();
				
				$options->limit = 100; /// Categories can be large, use limit
				
				$instances = smwfGetStore()->getPropertySubjects( SMWPropertyValue::makeProperty( '_INST' ), $value, $options );
				
				$pinst = SMWPropertyValue::makeProperty( '_INST' );
				
				foreach ( $instances as $instance ) {
					$stb = new SMWSmallTitle();
					$stb->dbkey = $instance->getDBkey();
					$stb->namespace = $instance->getNamespace();
					
					if ( !array_key_exists( $stb->getHash(), $this->element_done ) ) {
						$semdata = smwfGetStore()->getSemanticData( $instance, array( '__spu', '__typ', '__imp' ) );
						$semdata->addPropertyObjectValue( $pinst, $value );
						$data = SMWExporter::makeExportData( $semdata );
						$this->printExpData( $data );
					}
				}
			} elseif  ( SMW_NS_CONCEPT === $value->getNamespace() ) { // print concept members (slightly different code)
				$desc = new SMWConceptDescription( $value->getTitle() );
				$desc->addPrintRequest( new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, '' ) );
				$query = new SMWQuery( $desc );
				$query->setLimit( 100 );

				$res = smwfGetStore()->getQueryResult( $query );
				$resarray = $res->getNext();
				$pinst = SMWPropertyValue::makeProperty( '_INST' );
				
				while ( $resarray !== false ) {
					$instance = end( $resarray )->getNextObject();
					
					$stb = new SMWSmallTitle();
					
					$stb->dbkey = $instance->getDBkey();
					$stb->namespace = $instance->getNamespace();
					
					if ( !array_key_exists( $stb->getHash(), $this->element_done ) ) {
						$semdata = smwfGetStore()->getSemanticData( $instance,  array( '__spu', '__typ', '__imp' ) );
						$semdata->addPropertyObjectValue( $pinst, $value );
						$data = SMWExporter::makeExportData( $semdata );
						$this->printExpData( $data );
					}
					
					$resarray = $res->getNext();
				}
			}
			
			wfProfileOut( "RDF::PrintPages::GetBacklinks" );
		}
	}

	/**
	 * Flush all buffers and extra namespaces by printing them to stdout and flushing
	 * the output buffers afterwards.
	 *
	 * @param force if true, the flush cannot be delayed any longer
	 */
	protected function flushBuffers( $force = false ) {
		if ( $this->post_ns_buffer == '' ) return; // nothing to flush (every non-empty pre_ns_buffer also requires a non-empty post_ns_buffer)
		if ( ( 0 != $this->delay_flush ) && !$force ) return; // wait a little longer

		print $this->pre_ns_buffer;
		$this->pre_ns_buffer = '';

		foreach ( $this->extra_namespaces as $nsshort => $nsuri ) {
			if ( $this->first_flush ) {
				$this->global_namespaces[$nsshort] = true;
				print "\n\t";
			} else print ' ';

			print "xmlns:$nsshort=\"$nsuri\"";
		}

		$this->extra_namespaces = array();
		print $this->post_ns_buffer;
		$this->post_ns_buffer = '';
		
		// Ship data in small chunks (even though browsers often do not display anything
		// before the file is complete -- this might be due to syntax highlighting features
		// for app/xml). You may want to sleep(1) here for debugging this.
		ob_flush();
		flush();
		
		$this->first_flush = false;
	}

	/**
	 * Add an extra namespace that was encountered during output. The method
	 * checks whether the required namespace is available globally and adds
	 * it to the list of extra_namesapce otherwise.
	 */
	public function addExtraNamespace( $nsshort, $nsuri ) {
		if ( !array_key_exists( $nsshort, $this->global_namespaces ) ) {
			$this->extra_namespaces[$nsshort] = $nsuri;
		}
	}

	/**
	 * Add a given SMWExpResource to the export queue if needed.
	 */
	public function queueElement( $element ) {
		if ( !( $element instanceof SMWExpResource ) ) return; // only Resources are queued
		$title = $element->getDataValue();
		
		if ( $title instanceof SMWWikiPageValue ) {
			$spt = new SMWSmallTitle();
			$title = $title->getTitle();
			$spt->dbkey = $title->getDBkey();
			$spt->namespace = $title->getNamespace();
			$spt->modifier = $element->getModifier();
			
			if ( !array_key_exists( $spt->getHash(), $this->element_done ) ) {
				$this->element_queue[$spt->getHash()] = $spt;
			}
		}
	}

	/**
	 * Mark an article as done while making sure that the cache used for this
	 * stays reasonably small. Input is given as an SMWExportArticle object.
	 */
	protected function markAsDone( $st ) {
		if ( count( $this->element_done ) >= self::MAX_CACHE_SIZE ) {
			$this->element_done = array_slice( $this->element_done,
										self::CACHE_BACKJUMP,
										self::MAX_CACHE_SIZE - self::CACHE_BACKJUMP,
										true );
		}
		$this->element_done[$st->getHash()] = $st; // mark title as done
		unset( $this->element_queue[$st->getHash()] ); // make sure it is not in the queue
	}

	/**
	 * This function checks whether some article fits into a given namespace restriction.
	 * FALSE means "no restriction," non-negative restictions require to check whether
	 * the given number equals the given namespace. A restriction of -1 requires the
	 * namespace to be different from Category:, Relation:, Attribute:, and Type:.
	 */
	static public function fitsNsRestriction( $res, $ns ) {
		if ( $res === false ) return true;
		if ( is_array( $res ) ) return in_array( $ns, $res );
		if ( $res >= 0 ) return ( $res == $ns );
		return ( ( $res != NS_CATEGORY ) && ( $res != SMW_NS_PROPERTY ) && ( $res != SMW_NS_TYPE ) );
	}

}