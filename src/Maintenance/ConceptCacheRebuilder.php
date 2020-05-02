<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIConcept;
use SMW\MediaWiki\TitleLookup;
use SMW\Settings;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
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
	private $options = [];
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

		$options = [ 'hard', 'update', 'old', 'quiet', 'status', 'verbose' ];

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

		$actions = [ 'status', 'create', 'delete' ];

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

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$cliMsgFormatter->section( 'Concept(s)' )
		);

		if ( $this->hasOption( 'hard' ) ) {

			$this->reportMessage(
				"\nOption 'hard' defined with:\n"
			);

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... smwgQMaxDepth', $this->settings->get( 'smwgQMaxDepth' ), 3, '.' )
			);

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... smwgQMaxSize', $this->settings->get( 'smwgQMaxSize' ), 3, '.' )
			);

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... smwgQFeatures', $this->settings->get( 'smwgQFeatures' ), 3, '.' )
			);

			$this->reportMessage(
				$cliMsgFormatter->section( 'Tasks', 3, '-', true )
			);
		}

		switch ( $this->action ) {
			case 'status':
				$this->reportMessage( "\nCache status information ...\n" );
				break;
			case 'create':
				$this->reportMessage( "\nCreating (or updating) concept caches ...\n" );
				break;
			case 'delete':
				$delay = 5;
				$this->reportMessage( "\nAbort with CTRL-C in the next $delay seconds ... " );

				if ( !$this->hasOption( 'quiet' ) ) {
					swfCountDown( $delay );
				}

				$this->reportMessage( "\nDeleting concept caches ...\n" );
				break;
			default:
				return false;
		}

		$concepts = $this->getConcepts();

		foreach ( $concepts as $concept ) {
			$this->workOnConcept( $concept );
		}

		if ( $concepts === [] ) {
			$this->reportMessage( "No concept(s) available.\n" );
		} else {
			$this->reportMessage( "   ... done.\n" );
		}

		return true;
	}

	private function workOnConcept( Title $title ) {

		$concept = $this->store->getConceptCacheStatus( $title );

		if ( $this->skipConcept( $title, $concept ) ) {
			return $this->lines += $this->verbose ? 1 : 0;
		}

		$this->performAction( $title, $concept );

		return $this->lines += 1;
	}

	private function skipConcept( $title, $concept = null ) {

		$skip = false;

		if ( $concept === null ) {
			$skip = 'page not cacheable (no concept description, maybe a redirect)';
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

		$cliMsgFormatter = new CliMsgFormatter();

		if ( $this->action === 'create' ) {

			$this->store->refreshConceptCache( $title );

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... ' . $title->getPrefixedText() . ' ...', CliMsgFormatter::OK, 3 )
			);

			return;
		}

		if ( $this->action === 'delete' ) {

			$this->reportMessage(
				$cliMsgFormatter->oneCol( '... ' . $title->getPrefixedText() . ' ...', 3 )
			);

			return $this->store->deleteConceptCache( $title );
		}

		$this->reportMessage(
			$cliMsgFormatter->oneCol( '... ' . $title->getPrefixedText() . ' ...', 3 )
		);

		if ( $concept->getCacheStatus() === 'full' ) {

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... created at', $this->getCacheDateInfo( $concept->getCacheDate() ), 7 )
			);

			$this->reportMessage(
				$cliMsgFormatter->twoCols( '... elements count', $concept->getCacheCount(), 7, '.' )
			);
		} else {
			$this->reportMessage(
				$cliMsgFormatter->oneCol( '... not cached', 7 )
			);
		}
	}

	private function getConcepts() {

		if ( $this->concept !== null ) {
			return [ $this->createConcept() ];
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
		return date( 'Y-m-d H:i:s', $date ) . ' (' . floor( ( strtotime( 'now' ) - $date ) / 60 ) . ' minutes old)';
	}

}
