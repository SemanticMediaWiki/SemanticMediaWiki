<?php

/**
 * File holding the SMWExportController class that provides basic functions for
 * exporting pages to RDF and OWL.
 *
 * @file SMW_ExportController.php
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

/**
 * Small data object that specifies one wiki page to be serialised.
 * SMWSmallTitle objects are used to queue pages for serialisation, hence it
 * should be small to save memory.
 *
 * @ingroup SMW
 */
class SMWSmallTitle {
	/// DB key version of the title.
	public $dbkey;
	/// MediaWiki namespace constant.
	public $namespace;
	/**
	 * Recursion depth for serialising this object. Depth of 1 or above means
	 * the object is serialised with all property values, and referenced
	 * objects are serialised with depth reduced by 1. Depth 0 means that only
	 * minimal declarations are serialised, so no dependencies are added. A
	 * depth of -1 encodes "infinite" depth, i.e. a complete recursive
	 * serialisation without limit.
	 * @var integer
	 */
	public $recdepth = 1;

	public function getHash() {
		return $this->dbkey . ' ' . $this->namespace;
	}
}

/**
 * Class for controlling the export of SMW page data, supporting high-level
 * features such as recursive export and backlink inclusion. The class controls
 * export independent of the serialisation syntax that is used.
 *
 * @ingroup SMW
 */
class SMWExportController {
	const MAX_CACHE_SIZE = 5000; // do not let cache arrays get larger than this
	const CACHE_BACKJUMP = 500;  // kill this many cached entries if limit is reached,
	                             // avoids too much array copying; <= MAX_CACHE_SIZE!
	/**
	 * The object used for serialisation.
	 * @var SMWSerializer
	 */
	protected $serializer;
	/**
	 * An array that keeps track of the elements for which we still need to
	 * write auxiliary definitions/declarations.
	 */
	protected $element_queue;
	/**
	 * An array that keeps track of the recursion depth with which each object
	 * has been serialised.
	 */
	protected $element_done;
	/**
	 * Boolean to indicate whether all objects that are exported in full (with
	 * all data) should also lead to the inclusion of all "inlinks" that they
	 * receive from other objects. If yes, these other objects are also
	 * serialised with at least the relevant inlinking properties included.
	 * Adding such dependencies counts as "recursive serialisation" and whether
	 * or not inlinking objects are included in full depends on the setting for
	 * recursion depth. Setting this to true enables "browsable RDF".
	 */
	protected $add_backlinks;
	/**
	 * Controls how long to wait until flushing content to output. Flushing
	 * early may reduce the memory footprint of serialization functions.
	 * Flushing later has some advantages for export formats like RDF/XML where
	 * global namespace declarations are only possible by modifying the header,
	 * so that only local declarations are possible after the first flush.
	 */
	protected $delay_flush;
	/**
	 * File handle for a potential output file to write to, or null if printing
	 * to standard output.
	 */
	protected $outputfile;
	
	/**
	 * Constructor.
	 * @param SMWSerializer $serializer defining the object used for syntactic
	 * serialization.
	 * @param boolean $enable_backlinks defining if backlinks are included,
	 * see $add_backlinks for details.
	 */
	public function __construct( SMWSerializer $serializer, $enable_backlinks = false ) {
		$this->serializer = $serializer;
		$this->outputfile = null;
		$this->add_backlinks = $enable_backlinks;
	}
	
	/**
	 * Enable or disable inclusion of backlinks into the output.
	 * @param boolean $enable
	 */
	public function enableBacklinks( $enable ) {
		$this->add_backlinks = $enable;
	}

	/**
	 * Initialize all internal structures to begin with some serialization.
	 * Returns true if initialization was successful (this means that the
	 * optional output file is writable).
	 * @param string $outfilename URL of the file that output should be written
	 * to, or empty string for writting to the standard output. 
	 */
	protected function prepareSerialization( $outfilename = '' ) {
		$this->serializer->clear();
		$this->element_queue = array();
		$this->element_done = array();
		if ( $outfilename != '' ) {
			$this->outputfile = fopen( $outfilename, 'w' );
			if ( !$this->outputfile ) { // TODO Rather throw an exception here.
				print "\nCannot open \"$outfilename\" for writing.\n";
				return false;
			}
		}
		return true;
	}

	/**
	 * Serialize data associated to a specific page. This method works on the
	 * level of pages, i.e. it serialises parts of SMW content and implements
	 * features like recursive export or backlinks that are available for this
	 * type of data.
	 *
	 * @param SMWDIWikiPage $diWikiPage specifying the page to be exported
	 * @param integer $recursiondepth specifying the depth of recursion, see
	 * SMWSmallTitle::$recdepth
	 */
	protected function serializePage( SMWDIWikiPage $diWikiPage, $recursiondepth = 1 ) {
		$st = new SMWSmallTitle();
		$st->dbkey = $diWikiPage->getDBKey();
		$st->namespace = $diWikiPage->getNamespace();
		$st->recdepth = $recursiondepth;
		if ( $this->isDone( $st ) ) return; // do not export twice
		$this->markAsDone( $st );
		$data = SMWExporter::makeExportData( $this->getSemanticData( $diWikiPage, ( $recursiondepth == 0 ) ) );
		$this->serializer->serializeExpData( $data, $recursiondepth );

		// let other extensions add additional RDF data for this page
		$additionalDataArray = array();
		wfRunHooks( 'smwAddToRDFExport', array( $diWikiPage, &$additionalDataArray, ( $recursiondepth != 0 ), $this->add_backlinks ) );
		foreach ( $additionalDataArray as $additionalData ) {
			$this->serializer->serializeExpData( $additionalData ); // serialise
		}

		if ( $recursiondepth != 0 ) {
			$subrecdepth = $recursiondepth > 0 ? ( $recursiondepth - 1 ) :
			               ( $recursiondepth == 0 ? 0 : -1 );

			foreach ( $data->getProperties() as $property ) {
				if ( $property->getDataItem() instanceof SMWWikiPageValue ) {
					$this->queuePage( $property->getDataItem(), 0 ); // no real recursion along properties
				}
				$wikipagevalues = false;
				foreach ( $data->getValues( $property ) as $valueExpElement ) {
					$valueResource = $valueExpElement instanceof SMWExpData ? $valueExpElement->getSubject() : $valueExpElement;
					if ( !$wikipagevalues && ( $valueResource->getDataItem() instanceof SMWWikiPageValue ) ) {
						$wikipagevalues = true;
					} elseif ( !$wikipagevalues ) {
						break;
					}
					$this->queuePage( $valueResource->getDataItem(), $subrecdepth );
				}
			}
			
			// Add backlinks:
			// Note: Backlinks are different from recursive serialisations, since
			// stub declarations (recdepth==0) still need to have the property that
			// links back to the object. So objects that would be exported with
			// recdepth 0 cannot be put into the main queue but must be done right
			// away. They also might be required many times, if they link back to
			// many different objects in many ways (we cannot consider them "Done"
			// if they were serialised at recdepth 0 only).  
			if ( $this->add_backlinks ) {
				wfProfileIn( "RDF::PrintPages::GetBacklinks" );
				$inprops = smwfGetStore()->getInProperties( $diWikiPage );
				foreach ( $inprops as $inprop ) {
					$propWikiPage = $inprop->getDiWikiPage();
					if ( $propWikiPage !== null ) {
						$this->queuePage( $propWikiPage, 0 ); // no real recursion along properties
					}
					$inSubs = smwfGetStore()->getPropertySubjects( $inprop, $diWikiPage );
					foreach ( $inSubs as $inSub ) {
						$stb = new SMWSmallTitle();
						$stb->dbkey = $inSub->getDBkey();
						$stb->namespace = $inSub->getNamespace();
						$stb->recdepth = $subrecdepth;
						if ( !$this->isDone($stb) ) {
							$semdata = $this->getSemanticData( $inSub, true );
							$semdata->addPropertyObjectValue( $inprop, $diWikiPage );
							$data = SMWExporter::makeExportData( $semdata );
							$this->serializer->serializeExpData( $data, $subrecdepth );
						}
					}
				}
	
				if ( NS_CATEGORY === $diWikiPage->getNamespace() ) { // also print elements of categories
					$options = new SMWRequestOptions();
					$options->limit = 100; // Categories can be large, always use limit
					$instances = smwfGetStore()->getPropertySubjects( new SMWDIProperty( '_INST' ), $diWikiPage, $options );
					$pinst = new SMWDIProperty( '_INST' );
	
					foreach ( $instances as $instance ) {
						$stb = new SMWSmallTitle();
						$stb->dbkey = $instance->getDBkey();
						$stb->namespace = $instance->getNamespace();
	
						if ( !array_key_exists( $stb->getHash(), $this->element_done ) ) {
							$semdata = $this->getSemanticData( $instance, true );
							$semdata->addPropertyObjectValue( $pinst, $diWikiPage );
							$data = SMWExporter::makeExportData( $semdata );
							$this->serializer->serializeExpData( $data, $subrecdepth );
						}
					}
				} elseif ( SMW_NS_CONCEPT === $diWikiPage->getNamespace() ) { // print concept members (slightly different code)
					$desc = new SMWConceptDescription( $diWikiPage );
					$desc->addPrintRequest( new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, '' ) );
					$query = new SMWQuery( $desc );
					$query->setLimit( 100 );
	
					$res = smwfGetStore()->getQueryResult( $query );
					$resarray = $res->getNext();
					$pinst = new SMWDIProperty( '_INST' );
	
					while ( $resarray !== false ) {
						$instance = end( $resarray )->getNextDataValue();
	
						$stb = new SMWSmallTitle();
						$stb->dbkey = $instance->getDBkey();
						$stb->namespace = $instance->getNamespace();
	
						if ( !array_key_exists( $stb->getHash(), $this->element_done ) ) {
							$semdata = $this->getSemanticData( $instance, true );
							$semdata->addPropertyObjectValue( $pinst, $diWikiPage );
							$data = SMWExporter::makeExportData( $semdata );
							$this->serializer->serializeExpData( $data );
						}
	
						$resarray = $res->getNext();
					}
				}	
				wfProfileOut( "RDF::PrintPages::GetBacklinks" );
			}
		}
	}

	/**
	 * Serialize data associated to a specific page.
	 *
	 * @param SMWSmallTitle $st specifying the page to be exported
	 */
	protected function serializeSmallTitle( SMWSmallTitle $st ) {
		if ( $this->isDone( $st ) ) return; // do not export twice
		$diWikiPage = new SMWDIWikiPage( $st->dbkey, $st->namespace, '' );
		$this->serializePage( $diWikiPage, $st->recdepth );
	}

	/**
	 * Add a given SMWDIWikiPage to the export queue if needed.
	 */
	protected function queuePage( SMWDIWikiPage $diWikiPage, $recursiondepth ) {
		$spt = new SMWSmallTitle();
		$spt->dbkey = $diWikiPage->getDBkey();
		$spt->namespace = $diWikiPage->getNamespace();
		$spt->recdepth = $recursiondepth;
		if ( !$this->isDone( $spt ) ) {
			$this->element_queue[$spt->getHash()] = $spt;
		}
	}

	/**
	 * Mark an article as done while making sure that the cache used for this
	 * stays reasonably small. Input is given as an SMWSmallTitle object.
	 */
	protected function markAsDone( $st ) {
		if ( count( $this->element_done ) >= self::MAX_CACHE_SIZE ) {
			$this->element_done = array_slice( $this->element_done,
										self::CACHE_BACKJUMP,
										self::MAX_CACHE_SIZE - self::CACHE_BACKJUMP,
										true );
		}
		$hash = $st->getHash();
		if ( !$this->isDone( $st ) ) {
			$this->element_done[$hash] = $st->recdepth; // mark title as done, with given recursion
		}
		unset( $this->element_queue[$hash] ); // make sure it is not in the queue
	}
	
	/**
	 * Check if the given object has already been serialised at sufficient
	 * recursion depth.
	 * @param SMWSmallTitle $st specifying the object to check
	 */
	protected function isDone( SMWSmallTitle $st ) {
		$hash = $st->getHash();
		return ( ( array_key_exists( $hash, $this->element_done ) ) &&
		         ( ( $this->element_done[$hash] == -1 ) || 
		           ( ( $st->recdepth != -1 ) && ( $this->element_done[$hash] >= $st->recdepth ) ) ) ); 
	}

	/**
	 * Retrieve a copy of the semantic data for a wiki page, possibly filtering
	 * it so that only essential properties are included (in some cases, we only
	 * want to export stub information about a page).
	 * We make a copy of the object since we may want to add more data later on
	 * and we do not want to modify the store's result which may be used for
	 * caching purposes elsewhere.
	 */
	protected function getSemanticData( SMWDIWikiPage $diWikiPage, $core_props_only ) {
		$semdata = smwfGetStore()->getSemanticData( $diWikiPage, $core_props_only ? array( '__spu', '__typ', '__imp' ) : false ); // advise store to retrieve only core things
		if ( $core_props_only ) { // be sure to filter all non-relevant things that may still be present in the retrieved
			$result = new SMWSemanticData( $diWikiPage );
			foreach ( array( '_URI', '_TYPE', '_IMPO' ) as $propid ) {
				$prop = new SMWDIProperty( $propid );
				$values = $semdata->getPropertyValues( $prop );
				foreach ( $values as $dv ) {
					$result->addPropertyObjectValue( $prop, $dv );
				}
			}
		} else {
			$result = clone $semdata;
		}
		return $result;
	}
	
	/**
	 * Send to the output what has been serialized so far. The flush might
	 * be deferred until later unless $force is true.
	 */
	protected function flush( $force = false ) {
		if ( !$force && ( $this->delay_flush > 0 ) ) {
			$this->delay_flush -= 1;
		} elseif ( $this->outputfile !== null ) {
			fwrite( $this->outputfile, $this->serializer->flushContent() );
		} else {
			print $this->serializer->flushContent();
			// Ship data in small chunks (even though browsers often do not display anything
			// before the file is complete -- this might be due to syntax highlighting features
			// for app/xml). You may want to sleep(1) here for debugging this.
			ob_flush();
			flush();
		}
	}

	/**
	 * This function prints all selected pages, specified as an array of page
	 * names (strings with namespace identifiers).
	 * 
	 * @param array $pages list of page names to export
	 * @param integer $recursion determines how pages are exported recursively:
	 * "0" means that referenced resources are only declared briefly, "1" means
	 * that all referenced resources are also exported recursively (propbably
	 * retrieving the whole wiki).
	 * @param string $revisiondate filter page list by including only pages
	 * that have been changed since this date; format "YmdHis"
	 *
	 * @todo Consider dropping the $revisiondate filtering and all associated
	 * functionality. Is anybody using this?
	 */
	public function printPages( $pages, $recursion = 1, $revisiondate = false  ) {
		wfProfileIn( "RDF::PrintPages" );

		$linkCache =& LinkCache::singleton();
		$this->prepareSerialization();
		$this->delay_flush = 10; // flush only after (fully) printing 11 objects

		// transform pages into queued short titles
		foreach ( $pages as $page ) {
			$title = Title::newFromText( $page );
			if ( null === $title ) continue; // invalid title name given
			if ( $revisiondate !== '' ) { // filter page list by revision date
				$rev = Revision::getTimeStampFromID( $title, $title->getLatestRevID() );
				if ( $rev < $revisiondate ) continue;
			}
			$st = new SMWSmallTitle();
			$st->dbkey = $title->getDBkey();
			$st->namespace = $title->getNamespace();
			$st->recdepth = $recursion==1 ? -1 : 1;
			$this->element_queue[$st->getHash()] = $st;
		}

		$this->serializer->startSerialization();

		if ( count( $pages ) == 1 ) { // ensure that ontologies that are retrieved as linked data are not confused with their subject!
			$ontologyuri = SMWExporter::expandURI( '&export;' ) . '/' . urlencode( end( $pages ) );
		} else { // use empty URI, i.e. "location" as URI otherwise
			$ontologyuri = '';
		}
		$this->serializer->serializeExpData( SMWExporter::getOntologyExpData( $ontologyuri ) );

		while ( count( $this->element_queue ) > 0 ) {
			$this->serializeSmallTitle( reset( $this->element_queue ) );
			$this->flush();
			$linkCache->clear(); // avoid potential memory leak
		}
		$this->serializer->finishSerialization();
		$this->flush( true );

		wfProfileOut( "RDF::PrintPages" );
	}

	
	/**
	 * This function exports the semantic data for all pages within the wiki,
	 * and for all elements that are referred to in the exported data.
	 *
	 * @param string $outfile the output file URI, or false if printing to stdout
	 * @param mixed $ns_restriction namespace restriction, see fitsNsRestriction()
	 * @param integer $delay number of microseconds for which to sleep during
	 * export to reduce server load in long-running operations
	 * @param integer $delayeach number of pages to process between two sleeps 
	 */
	public function printAll( $outfile, $ns_restriction = false, $delay, $delayeach ) {
		$linkCache =& LinkCache::singleton();
		$db = wfGetDB( DB_SLAVE );

		$this->delay_flush = 10;
		if ( !$this->prepareSerialization( $outfile ) ) return;

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getOntologyExpData( '' ) );

		$end = $db->selectField( 'page', 'max(page_id)', false, $outfile );
		$a_count = 0; $d_count = 0; // DEBUG
		$delaycount = $delayeach;

		for ( $id = 1; $id <= $end; $id += 1 ) {
			$title = Title::newFromID( $id );
			if ( ( $title === null ) || !smwfIsSemanticsProcessed( $title->getNamespace() ) ) continue;
			if ( !SMWExportController::fitsNsRestriction( $ns_restriction, $title->getNamespace() ) ) continue;
			$a_count += 1; // DEBUG

			$st = new SMWSmallTitle();
			$st->dbkey = $title->getDBkey();
			$st->namespace = $title->getNamespace();
			$st->recdepth = 1;
			$this->element_queue[$st->getHash()] = $st;

			while ( count( $this->element_queue ) > 0 ) {
				$this->serializeSmallTitle( reset( $this->element_queue ) );
				// resolve dependencies that will otherwise not be printed
				foreach ( $this->element_queue as $key => $staux ) {
					if ( !smwfIsSemanticsProcessed( $staux->namespace ) ||
					     !SMWExportController::fitsNsRestriction( $ns_restriction, $staux->namespace ) ) {
						// Note: we do not need to check the cache to guess if an element was already
						// printed. If so, it would not be included in the queue in the first place.
						$d_count += 1; // DEBUG
					} else { // don't carry values that you do not want to export (yet)
						unset( $this->element_queue[$key] );
					}
				}
				// sleep each $delaycount for $delay µs to be nice to the server
				if ( ( $delaycount-- < 0 ) && ( $delayeach != 0 ) ) {
					usleep( $delay );
					$delaycount = $delayeach;
				}
			}

			$this->flush();
			$linkCache->clear();
		}
		
		$this->serializer->finishSerialization();
		$this->flush( true );
	}

	/**
	 * Print basic definitions a list of pages ordered by their page id.
	 * Offset and limit refer to the count of existing pages, not to the
	 * page id.
	 * @param integer $offset the number of the first (existing) page to
	 * serialize a declaration for
	 * @param integer $limit the number of pages to serialize
	 */
	public function printPageList( $offset = 0, $limit = 30 ) {
		global $smwgNamespacesWithSemanticLinks;
		wfProfileIn( "RDF::PrintPageList" );

		$db = wfGetDB( DB_SLAVE );
		$this->prepareSerialization();
		$this->delay_flush = 35; // don't do intermediate flushes with default parameters
		$linkCache = LinkCache::singleton();

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getOntologyExpData( '' ) );
		
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

		foreach ( $res as $row ) {
			$foundpages = true;
			$st = new SMWSmallTitle();
			$st->dbkey = $row->page_title;
			$st->namespace = $row->page_namespace;
			$st->recdepth = 0;
			$this->serializeSmallTitle( $st );
			$this->flush();
			$linkCache->clear();
		}
		if ( $foundpages ) { // add link to next result page
			if ( strpos( SMWExporter::expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
				$nexturl = SMWExporter::expandURI( '&export;?offset=' ) . ( $offset + $limit );
			} else {
				$nexturl = SMWExporter::expandURI( '&export;&amp;offset=' ) . ( $offset + $limit );
			}

			$data = new SMWExpData( new SMWExpResource( $nexturl ) );
			$ed = new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Thing' ) );
			$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $ed );
			$ed = new SMWExpData( new SMWExpResource( $nexturl ) );
			$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
			$this->serializer->serializeExpData( $data );
		}

		$this->serializer->finishSerialization();
		$this->flush( true );

		wfProfileOut( "RDF::PrintPageList" );
	}


	/**
	 * Print basic information about this site.
	 */
	public function printWikiInfo() {
		wfProfileIn( "RDF::PrintWikiInfo" );

		global $wgSitename, $wgLanguageCode;

		$db = & wfGetDB( DB_SLAVE );
		$this->prepareSerialization();
		$this->delay_flush = 35; // don't do intermediate flushes with default parameters
		$linkCache = LinkCache::singleton();
		
		// assemble export data: 
		$data = new SMWExpData( new SMWExpResource( '&wiki;#wiki' ) );
		$ed = new SMWExpData( SMWExporter::getSpecialNsResource( 'swivt', 'Wikisite' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $ed );
		// basic wiki information
		$ed = new SMWExpData( new SMWExpLiteral( $wgSitename ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'label' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( $wgSitename, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'siteName' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SMWExporter::expandURI( '&wikiurl;' ), null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'pagePrefix' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SMW_VERSION, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'smwVersion' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( $wgLanguageCode, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'langCode' ), $ed );
		$mainpage = Title::newMainPage();
		if ( $mainpage !== null ) {
			$ed = new SMWExpData( new SMWExpResource( $mainpage->getFullURL() ) );
			$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'mainPage' ), $ed );
		}
		// statistical information
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::pages(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'pageCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::articles(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'contentPageCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::images(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'mediaCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::edits(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'editCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::views(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'viewCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::users(), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'userCount' ), $ed );
		$ed = new SMWExpData( new SMWExpLiteral( SiteStats::numberingroup( 'sysop' ), null, 'http://www.w3.org/2001/XMLSchema#int' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'adminCount' ), $ed );

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getOntologyExpData( '' ) );
		$this->serializer->serializeExpData( $data );
		
		// link to list of existing pages:
		if ( strpos( SMWExporter::expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
			$nexturl = SMWExporter::expandURI( '&export;?offset=0' );
		} else {
			$nexturl = SMWExporter::expandURI( '&export;&amp;offset=0' );
		}
		$data = new SMWExpData( new SMWExpResource( $nexturl ) );
		$ed = new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Thing' ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $ed );
		$ed = new SMWExpData( new SMWExpResource( $nexturl ) );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
		$this->serializer->serializeExpData( $data );

		$this->serializer->finishSerialization();
		$this->flush( true );

		wfProfileOut( "RDF::PrintWikiInfo" );
	}
	
	/**
	 * This function checks whether some article fits into a given namespace
	 * restriction. Restrictions are encoded as follows: a non-negative number
	 * requires the namespace to be identical to the given number; "-1"
	 * requires the namespace to be different from Category, Property, and
	 * Type; "false" means "no restriction".
	 * @param $res mixed encoding the restriction as described above
	 * @param $ns integer the namespace constant to be checked
	 */
	static public function fitsNsRestriction( $res, $ns ) {
		if ( $res === false ) return true;
		if ( is_array( $res ) ) return in_array( $ns, $res );
		if ( $res >= 0 ) return ( $res == $ns );
		return ( ( $res != NS_CATEGORY ) && ( $res != SMW_NS_PROPERTY ) && ( $res != SMW_NS_TYPE ) );
	}
	
}
