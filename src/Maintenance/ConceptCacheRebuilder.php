<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIConcept;
use SMW\MediaWiki\TitleLookup;
use SMW\Settings;
use SMW\Store;
use Title;

/**
 * Is part of the `rebuildConceptCache.php` maintenance script to rebuild
 * cache entries for selected concepts
 *
 * @note This is an internal class and should not be used outside of smw-core
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class ConceptCacheRebuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	private $concept = null;
	private $action  = null;
	private $options = array();
	private $startId = 0;
	private $endId   = 0;
	private $lines   = 0;
	private $verbose = false;

	/**
	 * @since 1.9.2
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
		$this->reporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since 2.1
	 *
	 * @param MessageReporter $reporter
	 */
	public function setMessageReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param array $parameters
	 */
	public function setParameters( array $parameters ) {

		$options = array( 'hard', 'update', 'old', 'quiet', 'status', 'verbose' );

		foreach ( $options as $option ) {
			if ( isset( $parameters[$option] ) ) {
				$this->options[$option] = $parameters[$option];
			}
		}

		if ( isset( $parameters['concept'] ) ) {
			$this->concept = $parameters['concept'];
		}

		if ( isset( $parameters['s'] ) ) {
			$this->startId = intval( $parameters['s'] );
		}

		if ( isset( $parameters['e'] ) ) {
			$this->endId = intval( $parameters['e'] );
		}

		$actions = array( 'status', 'create', 'delete' );

		foreach ( $actions as $action ) {
			if ( isset( $parameters[$action] ) && $this->action === null ) {
				$this->action = $action;
			}
		}

		$this->verbose = array_key_exists( 'verbose', $parameters );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 */
	public function rebuild() {

		switch ( $this->action ) {
			case 'status':
				$this->reportMessage( "\nDisplaying concept cache status information. Use CTRL-C to abort.\n\n" );
				break;
			case 'create':
				$this->reportMessage(  "\nCreating/updating concept caches. Use CTRL-C to abort.\n\n" );
				break;
			case 'delete':
				$delay = 5;
				$this->reportMessage( "\nAbort with CTRL-C in the next $delay seconds ... " );

				if ( !$this->hasOption( 'quiet' ) ) {
					wfCountDown( $delay );
				}

				$this->reportMessage( "\nDeleting concept caches.\n\n" );
				break;
			default:
				return false;
		}

		if ( $this->hasOption( 'hard' ) ) {

			$settings  = ' smwgQMaxDepth: ' . $this->settings->get( 'smwgQMaxDepth' );
			$settings .= ' smwgQMaxSize: '  . $this->settings->get( 'smwgQMaxSize' );
			$settings .= ' smwgQFeatures: ' . $this->settings->get( 'smwgQFeatures' );

			$this->reportMessage( "Option 'hard' is parameterized by{$settings}\n\n" );
		}

		$concepts = $this->getConcepts();

		foreach ( $concepts as $concept ) {
			$this->workOnConcept( $concept );
		}

		if ( $concepts === array() ) {
			$this->reportMessage( "No concept available.\n" );
		} else {
			$this->reportMessage( "\nDone.\n" );
		}

		return true;
	}

	private function workOnConcept( Title $title ) {

		$concept = $this->store->getConceptCacheStatus( $title );

		if ( $this->skipConcept( $title, $concept ) ) {
			return $this->lines += $this->verbose ? 1 : 0;
		}

		$result = $this->performAction( $title, $concept );

		if ( $result ) {
			$this->reportMessage( '  ' . implode( $result, "\n  " ) . "\n" );
		}

		return $this->lines += 1;
	}

	private function skipConcept( $title, $concept = null ) {

		$skip = false;

		if ( $concept === null ) {
			$skip = 'page not cachable (no concept description, maybe a redirect)';
		} elseif ( ( $this->hasOption( 'update' ) ) && ( $concept->getCacheStatus() !== 'full' ) ) {
			$skip = 'page not cached yet';
		} elseif ( ( $this->hasOption( 'old' ) ) && ( $concept->getCacheStatus() === 'full' ) &&
			( $concept->getCacheDate() > ( strtotime( 'now' ) - intval( $this->options['old'] ) * 60 ) ) ) {
			$skip = 'cache is not old yet';
		} elseif ( ( $this->hasOption( 'hard' ) ) && ( $this->settings->get( 'smwgQMaxSize' ) >= $concept->getSize() ) &&
					( $this->settings->get( 'smwgQMaxDepth' ) >= $concept->getDepth() &&
					( ( ~( ~( $concept->getQueryFeatures() + 0 ) | $this->settings->get( 'smwgQFeatures' ) ) ) == 0 ) ) ) {
			$skip = 'concept is not "hard" according to wiki settings';
		}

		if ( $skip ) {
			$line = $this->lines !== false ? "($this->lines) " : '';
			$this->reportMessage( $line . 'Skipping concept "' . $title->getPrefixedText() . "\": $skip\n", $this->verbose );
		}

		return $skip;
	}

	private function performAction( Title $title, DIConcept $concept ) {
		$this->reportMessage( "($this->lines) " );

		if ( $this->action ===  'create' ) {
			$this->reportMessage( 'Creating cache for "' . $title->getPrefixedText() . "\" ...\n" );
			return $this->store->refreshConceptCache( $title );
		}

		if ( $this->action === 'delete' ) {
			$this->reportMessage( 'Deleting cache for "' . $title->getPrefixedText() . "\" ...\n" );
			return $this->store->deleteConceptCache( $title );
		}

		$this->reportMessage( 'Status for "' . $title->getPrefixedText() . '": ' );

		if ( $concept->getCacheStatus() === 'full' ) {
			$this->reportMessage( 'Cache created at ' .
				$this->getCacheDateInfo( $concept->getCacheDate() ) .
				"{$concept->getCacheCount()} elements in cache\n"
			);
		}
		else {
			$this->reportMessage( "Not cached.\n" );
		}
	}

	private function getConcepts() {

		if ( $this->concept !== null ) {
			return array( $this->createConcept() );
		}

		return $this->createMultipleConcepts();
	}

	private function createConcept() {
		return Title::newFromText( $this->concept, SMW_NS_CONCEPT );
	}

	private function createMultipleConcepts() {

		$titleLookup = new TitleLookup( $this->store->getConnection( 'mw.db' ) );
		$titleLookup->setNamespace( SMW_NS_CONCEPT );

		if ( $this->endId == 0 && $this->startId == 0 ) {
			return $titleLookup->selectAll();
		}

		$endId = $titleLookup->getMaxId();

		if ( $this->endId > 0 ) {
			$endId = min( $this->endId, $endId );
		}

		return $titleLookup->selectByIdRange( $this->startId, $endId );
	}

	private function hasOption( $key ) {
		return isset( $this->options[$key] );
	}

	private function reportMessage( $message, $output = true ) {
		if ( $output ) {
			$this->reporter->reportMessage( $message );
		}
	}

	private function getCacheDateInfo( $date ) {
		return date( 'Y-m-d H:i:s', $date ) . ' (' . floor( ( strtotime( 'now' ) - $date ) / 60 ) . ' minutes old), ';
	}

}
