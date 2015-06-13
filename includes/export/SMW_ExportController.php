<?php

use SMW\ApplicationFactory;
use SMW\Query\PrintRequest;
use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * File holding the SMWExportController class that provides basic functions for
 * exporting pages to RDF and OWL.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

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
	 * @var DeepRedirectTargetResolver
	 */
	private $deepRedirectTargetResolver = null;

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
	 *
	 * @return boolean
	 */
	protected function prepareSerialization( $outfilename = '' ) {
		$this->serializer->clear();
		$this->element_queue = array();
		$this->element_done = array();
		if ( $outfilename !== '' ) {
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
	 * The recursion depth means the following. Depth of 1 or above means
	 * the object is serialised with all property values, and referenced
	 * objects are serialised with depth reduced by 1. Depth 0 means that only
	 * minimal declarations are serialised, so no dependencies are added. A
	 * depth of -1 encodes "infinite" depth, i.e. a complete recursive
	 * serialisation without limit.
	 *
	 * @param SMWDIWikiPage $diWikiPage specifying the page to be exported
	 * @param integer $recursiondepth specifying the depth of recursion
	 */
	protected function serializePage( SMWDIWikiPage $diWikiPage, $recursiondepth = 1 ) {

		if ( $this->isPageDone( $diWikiPage, $recursiondepth ) ) {
			return; // do not export twice
		}

		$this->markPageAsDone( $diWikiPage, $recursiondepth );
		$semData = $this->getSemanticData( $diWikiPage, ( $recursiondepth == 0 ) );

		// Don't try to serialize an empty page that cause an incomplete exp-data set
		// (e.g. _REDI as no property page hence DBKey is empty)
		if ( $semData === null || $diWikiPage->getDBKey() === '' ) {
			return null;
		}

		$expData = SMWExporter::getInstance()->makeExportData( $semData );
		$this->serializer->serializeExpData( $expData, $recursiondepth );

		foreach( $semData->getSubSemanticData() as $subobjectSemData ) {
			$this->serializer->serializeExpData( SMWExporter::getInstance()->makeExportData( $subobjectSemData ) );
		}

		// let other extensions add additional RDF data for this page
		$additionalDataArray = array();
		wfRunHooks( 'smwAddToRDFExport', array( $diWikiPage, &$additionalDataArray, ( $recursiondepth != 0 ), $this->add_backlinks ) );
		foreach ( $additionalDataArray as $additionalData ) {
			$this->serializer->serializeExpData( $additionalData ); // serialise
		}

		if ( $recursiondepth != 0 ) {
			$subrecdepth = $recursiondepth > 0 ? ( $recursiondepth - 1 ) :
			               ( $recursiondepth == 0 ? 0 : -1 );

			foreach ( $expData->getProperties() as $property ) {
				if ( $property->getDataItem() instanceof SMWWikiPageValue ) {
					$this->queuePage( $property->getDataItem(), 0 ); // no real recursion along properties
				}
				$wikipagevalues = false;
				foreach ( $expData->getValues( $property ) as $valueExpElement ) {
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
				$inprops = \SMW\StoreFactory::getStore()->getInProperties( $diWikiPage );

				foreach ( $inprops as $inprop ) {
					$propWikiPage = $inprop->getDiWikiPage();

					if ( !is_null( $propWikiPage ) ) {
						$this->queuePage( $propWikiPage, 0 ); // no real recursion along properties
					}

					$inSubs = \SMW\StoreFactory::getStore()->getPropertySubjects( $inprop, $diWikiPage );

					foreach ( $inSubs as $inSub ) {
						if ( !$this->isPageDone( $inSub, $subrecdepth ) ) {
							$semdata = $this->getSemanticData( $inSub, true );

							if ( !$semdata instanceof SMWSemanticData ) {
								continue;
							}

							$semdata->addPropertyObjectValue( $inprop, $diWikiPage );
							$expData = SMWExporter::getInstance()->makeExportData( $semdata );
							$this->serializer->serializeExpData( $expData, $subrecdepth );
						}
					}
				}

				if ( NS_CATEGORY === $diWikiPage->getNamespace() ) { // also print elements of categories
					$options = new SMWRequestOptions();
					$options->limit = 100; // Categories can be large, always use limit
					$instances = \SMW\StoreFactory::getStore()->getPropertySubjects( new SMWDIProperty( '_INST' ), $diWikiPage, $options );
					$pinst = new SMWDIProperty( '_INST' );

					foreach ( $instances as $instance ) {
						if ( !array_key_exists( $instance->getHash(), $this->element_done ) ) {
							$semdata = $this->getSemanticData( $instance, true );

							if ( !$semdata instanceof SMWSemanticData ) {
								continue;
							}

							$semdata->addPropertyObjectValue( $pinst, $diWikiPage );
							$expData = SMWExporter::getInstance()->makeExportData( $semdata );
							$this->serializer->serializeExpData( $expData, $subrecdepth );
						}
					}
				} elseif ( SMW_NS_CONCEPT === $diWikiPage->getNamespace() ) { // print concept members (slightly different code)
					$desc = new SMWConceptDescription( $diWikiPage );
					$desc->addPrintRequest( new PrintRequest( PrintRequest::PRINT_THIS, '' ) );
					$query = new SMWQuery( $desc );
					$query->setLimit( 100 );

					$res = \SMW\StoreFactory::getStore()->getQueryResult( $query );
					$resarray = $res->getNext();
					$pinst = new SMWDIProperty( '_INST' );

					while ( $resarray !== false ) {
						$instance = end( $resarray )->getNextDataItem();

						if ( !array_key_exists( $instance->getHash(), $this->element_done ) ) {
							$semdata = $this->getSemanticData( $instance, true );
							$semdata->addPropertyObjectValue( $pinst, $diWikiPage );
							$expData = SMWExporter::getInstance()->makeExportData( $semdata );
							$this->serializer->serializeExpData( $expData );
						}

						$resarray = $res->getNext();
					}
				}
			}
		}
	}

	/**
	 * Add a given SMWDIWikiPage to the export queue if needed.
	 */
	protected function queuePage( SMWDIWikiPage $diWikiPage, $recursiondepth ) {
		if ( !$this->isPageDone( $diWikiPage, $recursiondepth ) ) {
			$diWikiPage->recdepth = $recursiondepth; // add a field
			$this->element_queue[$diWikiPage->getHash()] = $diWikiPage;
		}
	}

	/**
	 * Mark an article as done while making sure that the cache used for this
	 * stays reasonably small. Input is given as an SMWDIWikiPage object.
	 */
	protected function markPageAsDone( SMWDIWikiPage $di, $recdepth ) {
		$this->markHashAsDone( $di->getHash(), $recdepth );
	}

	/**
	 * Mark a task as done while making sure that the cache used for this
	 * stays reasonably small.
	 */
	protected function markHashAsDone( $hash, $recdepth ) {
		if ( count( $this->element_done ) >= self::MAX_CACHE_SIZE ) {
			$this->element_done = array_slice( $this->element_done,
				self::CACHE_BACKJUMP,
				self::MAX_CACHE_SIZE - self::CACHE_BACKJUMP,
				true );
		}
		if ( !$this->isHashDone( $hash, $recdepth ) ) {
			$this->element_done[$hash] = $recdepth; // mark title as done, with given recursion
		}
		unset( $this->element_queue[$hash] ); // make sure it is not in the queue
	}

	/**
	 * Check if the given object has already been serialised at sufficient
	 * recursion depth.
	 * @param SMWDIWikiPage $st specifying the object to check
	 *
	 * @return boolean
	 */
	protected function isPageDone( SMWDIWikiPage $di, $recdepth ) {
		return $this->isHashDone( $di->getHash(), $recdepth );
	}

	/**
	 * Check if the given task has already been completed at sufficient
	 * recursion depth.
	 */
	protected function isHashDone( $hash, $recdepth ) {
		return ( ( array_key_exists( $hash, $this->element_done ) ) &&
		         ( ( $this->element_done[$hash] == -1 ) ||
		           ( ( $recdepth != -1 ) && ( $this->element_done[$hash] >= $recdepth ) ) ) );
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

		// Issue 619
		// Resolve the redirect target and return a container with information
		// about the redirect
		if ( $diWikiPage->getTitle() !== null && $diWikiPage->getTitle()->isRedirect() ) {

			try {
				$redirectTarget = $this->getDeepRedirectTargetResolver()->findRedirectTargetFor( $diWikiPage->getTitle() );
			} catch ( \Exception $e ) {
				$redirectTarget = null;
			}

			// Couldn't resolve the redirect which is most likely caused by a
			// circular redirect therefore we give up
			if ( $redirectTarget === null ) {
				return null;
			}

			$semData = new SemanticData( $diWikiPage );

			$semData->addPropertyObjectValue(
				new DIProperty( '_REDI' ),
				DIWikiPage::newFromTitle( $redirectTarget )
			);

			return $semData;
		}

		$semdata = \SMW\StoreFactory::getStore()->getSemanticData( $diWikiPage, $core_props_only ? array( '__spu', '__typ', '__imp' ) : false ); // advise store to retrieve only core things
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
		} elseif ( !is_null( $this->outputfile ) ) {
			fwrite( $this->outputfile, $this->serializer->flushContent() );
		} else {
			ob_start();
			print $this->serializer->flushContent();
			// Ship data in small chunks (even though browsers often do not display anything
			// before the file is complete -- this might be due to syntax highlighting features
			// for app/xml). You may want to sleep(1) here for debugging this.
			ob_flush();
			flush();
			ob_get_clean();
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

		$linkCache = LinkCache::singleton();
		$this->prepareSerialization();
		$this->delay_flush = 10; // flush only after (fully) printing 11 objects

		// transform pages into queued short titles
		foreach ( $pages as $page ) {
			$title = Title::newFromText( $page );
			if ( null === $title ) {
				continue; // invalid title name given
			}
			if ( $revisiondate !== '' ) { // filter page list by revision date
				$rev = Revision::getTimeStampFromID( $title, $title->getLatestRevID() );
				if ( $rev < $revisiondate ) {
					continue;
				}
			}

			$diPage = SMWDIWikiPage::newFromTitle( $title );
			$this->queuePage( $diPage, ( $recursion==1 ? -1 : 1 ) );
		}

		$this->serializer->startSerialization();

		if ( count( $pages ) == 1 ) { // ensure that ontologies that are retrieved as linked data are not confused with their subject!
			$ontologyuri = SMWExporter::getInstance()->expandURI( '&export;' ) . '/' . urlencode( end( $pages ) );
		} else { // use empty URI, i.e. "location" as URI otherwise
			$ontologyuri = '';
		}
		$this->serializer->serializeExpData( SMWExporter::getInstance()->getOntologyExpData( $ontologyuri ) );

		while ( count( $this->element_queue ) > 0 ) {
			$diPage = reset( $this->element_queue );
			$this->serializePage( $diPage, $diPage->recdepth );
			$this->flush();
			$linkCache->clear(); // avoid potential memory leak
		}
		$this->serializer->finishSerialization();
		$this->flush( true );

	}

	/**
	 * Exports semantic data for all pages within the wiki and for all elements
	 * that are referred to a file resource
	 *
	 * @since  2.0
	 *
	 * @param string $outfile the output file URI, or false if printing to stdout
	 * @param mixed $ns_restriction namespace restriction, see fitsNsRestriction()
	 * @param integer $delay number of microseconds for which to sleep during
	 * export to reduce server load in long-running operations
	 * @param integer $delayeach number of pages to process between two sleeps
	 */
	public function printAllToFile( $outfile, $ns_restriction = false, $delay, $delayeach ) {

		if ( !$this->prepareSerialization( $outfile ) ) {
			return;
		}

		$this->printAll( $ns_restriction, $delay, $delayeach );
	}

	/**
	 * Exports semantic data for all pages within the wiki and for all elements
	 * that are referred to the stdout
	 *
	 * @since  2.0
	 *
	 * @param mixed $ns_restriction namespace restriction, see fitsNsRestriction()
	 * @param integer $delay number of microseconds for which to sleep during
	 * export to reduce server load in long-running operations
	 * @param integer $delayeach number of pages to process between two sleeps
	 */
	public function printAllToOutput( $ns_restriction = false, $delay, $delayeach ) {
		$this->prepareSerialization();
		$this->printAll( $ns_restriction, $delay, $delayeach );
	}

	/**
	 * @since 2.0 made protected; use printAllToFile or printAllToOutput
	 */
	protected function printAll( $ns_restriction = false, $delay, $delayeach ) {
		$linkCache = LinkCache::singleton();
		$db = wfGetDB( DB_SLAVE );

		$this->delay_flush = 10;

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getInstance()->getOntologyExpData( '' ) );

		$end = $db->selectField( 'page', 'max(page_id)', false, __METHOD__ );
		$a_count = 0; // DEBUG
		$d_count = 0; // DEBUG
		$delaycount = $delayeach;

		for ( $id = 1; $id <= $end; $id += 1 ) {
			$title = Title::newFromID( $id );
			if ( is_null( $title ) || !smwfIsSemanticsProcessed( $title->getNamespace() ) ) {
				continue;
			}
			if ( !self::fitsNsRestriction( $ns_restriction, $title->getNamespace() ) ) {
				continue;
			}
			$a_count += 1; // DEBUG

			$diPage = SMWDIWikiPage::newFromTitle( $title );
			$this->queuePage( $diPage, 1 );

			while ( count( $this->element_queue ) > 0 ) {
				$diPage = reset( $this->element_queue );
				$this->serializePage( $diPage, $diPage->recdepth );
				// resolve dependencies that will otherwise not be printed
				foreach ( $this->element_queue as $key => $diaux ) {
					if ( !smwfIsSemanticsProcessed( $diaux->getNamespace() ) ||
					     !self::fitsNsRestriction( $ns_restriction, $diaux->getNamespace() ) ) {
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

		$db = wfGetDB( DB_SLAVE );
		$this->prepareSerialization();
		$this->delay_flush = 35; // don't do intermediate flushes with default parameters
		$linkCache = LinkCache::singleton();

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getInstance()->getOntologyExpData( '' ) );

		$query = '';
		foreach ( $smwgNamespacesWithSemanticLinks as $ns => $enabled ) {
			if ( $enabled ) {
				if ( $query !== '' ) {
					$query .= ' OR ';
				}
				$query .= 'page_namespace = ' . $db->addQuotes( $ns );
			}
		}
		$res = $db->select( $db->tableName( 'page' ),
		                    'page_id,page_title,page_namespace', $query,
		                    'SMW::RDF::PrintPageList', array( 'ORDER BY' => 'page_id ASC', 'OFFSET' => $offset, 'LIMIT' => $limit ) );
		$foundpages = false;

		foreach ( $res as $row ) {
			$foundpages = true;
			try {
				$diPage = new SMWDIWikiPage( $row->page_title, $row->page_namespace, '' );
				$this->serializePage( $diPage, 0 );
				$this->flush();
				$linkCache->clear();
			} catch ( SMWDataItemException $e ) {
				// strange data, who knows, not our DB table, keep calm and carry on
			}
		}

		if ( $foundpages ) { // add link to next result page
			if ( strpos( SMWExporter::getInstance()->expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
				$nexturl = SMWExporter::getInstance()->expandURI( '&export;?offset=' ) . ( $offset + $limit );
			} else {
				$nexturl = SMWExporter::getInstance()->expandURI( '&export;&amp;offset=' ) . ( $offset + $limit );
			}

			$expData = new SMWExpData( new SMWExpResource( $nexturl ) );
			$ed = new SMWExpData( SMWExporter::getInstance()->getSpecialNsResource( 'owl', 'Thing' ) );
			$expData->addPropertyObjectValue( SMWExporter::getInstance()->getSpecialNsResource( 'rdf', 'type' ), $ed );
			$ed = new SMWExpData( new SMWExpResource( $nexturl ) );
			$expData->addPropertyObjectValue( SMWExporter::getInstance()->getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
			$this->serializer->serializeExpData( $expData );
		}

		$this->serializer->finishSerialization();
		$this->flush( true );

	}


	/**
	 * Print basic information about this site.
	 */
	public function printWikiInfo() {

		global $wgSitename, $wgLanguageCode;

		$this->prepareSerialization();
		$this->delay_flush = 35; // don't do intermediate flushes with default parameters

		// assemble export data:
		$expData = new SMWExpData( new SMWExpResource( '&wiki;#wiki' ) );

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'rdf', 'type' ),
			new SMWExpData( SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'Wikisite' ) )
		);

		// basic wiki information
		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'rdfs', 'label' ),
			new SMWExpLiteral( $wgSitename )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'siteName' ),
			new SMWExpLiteral( $wgSitename, 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'pagePrefix' ),
			new SMWExpLiteral( SMWExporter::getInstance()->expandURI( '&wikiurl;' ), 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'smwVersion' ),
			new SMWExpLiteral( SMW_VERSION, 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'langCode' ),
			new SMWExpLiteral( $wgLanguageCode, 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$mainpage = Title::newMainPage();

		if ( !is_null( $mainpage ) ) {
			$ed = new SMWExpData( new SMWExpResource( $mainpage->getFullURL() ) );
			$expData->addPropertyObjectValue( SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'mainPage' ), $ed );
		}

		// statistical information
		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'pageCount' ),
			new SMWExpLiteral( SiteStats::pages(), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'contentPageCount' ),
			new SMWExpLiteral( SiteStats::articles(), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'mediaCount' ),
			new SMWExpLiteral( SiteStats::images(), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'editCount' ),
			new SMWExpLiteral( SiteStats::edits(), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		// SiteStats::views was deprecated in MediaWiki 1.25
		// "Stop calling this function, it will be removed some time in the future"
		//$expData->addPropertyObjectValue(
		//	SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'viewCount' ),
		//	new SMWExpLiteral( SiteStats::views(), 'http://www.w3.org/2001/XMLSchema#int' )
		//);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'userCount' ),
			new SMWExpLiteral( SiteStats::users(), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		$expData->addPropertyObjectValue(
			SMWExporter::getInstance()->getSpecialNsResource( 'swivt', 'adminCount' ),
			new SMWExpLiteral( SiteStats::numberingroup( 'sysop' ), 'http://www.w3.org/2001/XMLSchema#int' )
		);

		$this->serializer->startSerialization();
		$this->serializer->serializeExpData( SMWExporter::getInstance()->getOntologyExpData( '' ) );
		$this->serializer->serializeExpData( $expData );

		// link to list of existing pages:
		if ( strpos( SMWExporter::getInstance()->expandURI( '&wikiurl;' ), '?' ) === false ) { // check whether we have title as a first parameter or in URL
			$nexturl = SMWExporter::getInstance()->expandURI( '&export;?offset=0' );
		} else {
			$nexturl = SMWExporter::getInstance()->expandURI( '&export;&amp;offset=0' );
		}
		$expData = new SMWExpData( new SMWExpResource( $nexturl ) );
		$ed = new SMWExpData( SMWExporter::getInstance()->getSpecialNsResource( 'owl', 'Thing' ) );
		$expData->addPropertyObjectValue( SMWExporter::getInstance()->getSpecialNsResource( 'rdf', 'type' ), $ed );
		$ed = new SMWExpData( new SMWExpResource( $nexturl ) );
		$expData->addPropertyObjectValue( SMWExporter::getInstance()->getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
		$this->serializer->serializeExpData( $expData );

		$this->serializer->finishSerialization();
		$this->flush( true );

	}

	/**
	 * This function checks whether some article fits into a given namespace
	 * restriction. Restrictions are encoded as follows: a non-negative number
	 * requires the namespace to be identical to the given number; "-1"
	 * requires the namespace to be different from Category, Property, and
	 * Type; "false" means "no restriction".
	 *
	 * @param $res mixed encoding the restriction as described above
	 * @param $ns integer the namespace constant to be checked
	 *
	 * @return boolean
	 */
	static public function fitsNsRestriction( $res, $ns ) {
		if ( $res === false ) {
			return true;
		}
		if ( is_array( $res ) ) {
			return in_array( $ns, $res );
		}
		if ( $res >= 0 ) {
			return ( $res == $ns );
		}
		return ( ( $res != NS_CATEGORY ) && ( $res != SMW_NS_PROPERTY ) && ( $res != SMW_NS_TYPE ) );
	}

	private function getDeepRedirectTargetResolver() {

		if ( $this->deepRedirectTargetResolver === null ) {
			$this->deepRedirectTargetResolver = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newDeepRedirectTargetResolver();
		}

		return $this->deepRedirectTargetResolver;
	}

}
