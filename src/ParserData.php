<?php

namespace SMW;

use ParserOutput;
use ParserOptions;
use SMWDataValue as DataValue;
use Title;
use Onoi\Cache\Cache;

/**
 * Handling semantic data exchange with a ParserOutput object
 *
 * Provides access to a semantic data container that is generated
 * either from the ParserOutput or is a newly created container
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ParserData {

	/**
	 * Identifies the extension data
	 */
	const DATA_ID = 'smwdata';

	/**
	 * Identifies the cache namespace for update markers
	 */
	const CACHE_NAMESPACE = 'smw:update';

	/**
	 * Option that allows to force an update even in cases where an update
	 * marker exists
	 */
	const OPT_FORCED_UPDATE = 'smw:opt.forced.update';

	/**
	 * Option whether creation of iteratibe update jobs are allowed
	 */
	const OPT_CREATE_UPDATE_JOB = 'smw:opt.create.update.job';

	/**
	 * Indicates that an update was caused by a change propagation request
	 */
	const OPT_CHANGE_PROP_UPDATE = 'smw:opt.change.prop.update';

	/**
	 * Indicates that no #ask dependency tracking should occur
	 */
	const NO_QUERY_DEPENDENCY_TRACE = 'no.query.dependency.trace';

	/**
	 * Indicates that no #ask dependency tracking should occur
	 */
	const ANNOTATION_BLOCK = 'smw-blockannotation';

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var ParserOptions
	 */
	private $parserOptions;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var $canCreateUpdateJob
	 */
	private $canCreateUpdateJob = true;

	/**
	 * Identifies the origin of a request.
	 *
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var Options
	 */
	private $options = null;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @param Cache|null $cache
	 */
	public function __construct( Title $title, ParserOutput $parserOutput, Cache $cache = null ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = ApplicationFactory::getInstance()->getCache();
		}

		$this->initSemanticData();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {

		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setOption( $key, $value ) {

		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		return $this->options->set( $key, $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->getSemanticData()->getSubject();
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * @since 3.0
	 *
	 * @param ParserOptions $parserOptions
	 */
	public function setParserOptions( ParserOptions $parserOptions ) {
		$this->parserOptions = $parserOptions;
	}

	/**
	 * @since 3.0
	 *
	 * @return ParserOptions|null
	 */
	public function addExtraParserKey( $key ) {
		// Looks odd in 1.30 "Saved in parser cache ... idhash:19989-0!canonical!userlang!dateformat!userlang!dateformat!userlang!dateformat!userlang!dateformat and ..."
		// threfore use the ParserOutput::recordOption instead
		if ( $key === 'userlang' || $key === 'dateformat' ) {
			$this->parserOutput->recordOption( $key );
		} elseif ( $this->parserOptions !== null ) {
			$this->parserOptions->addExtraKey( $key );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function isBlocked() {

		// ParserOutput::getExtensionData returns null if no value was set for this key
		if ( $this->parserOutput->getExtensionData( self::ANNOTATION_BLOCK ) !== null &&
			$this->parserOutput->getExtensionData( self::ANNOTATION_BLOCK ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function canUse() {
		return !$this->isBlocked();
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since  1.9
	 */
	public function addError( $error ) {
		$this->errors = array_merge( $this->errors, (array)$error );
	}

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @deprecated since 2.0, use setSemanticData
	 */
	public function setData( SemanticData $semanticData ) {
		$this->setSemanticData( $semanticData );
	}

	/**
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * @deprecated since 2.0, use getSemanticData
	 */
	public function getData() {
		return $this->getSemanticData();
	}

	/**
	 * @since 2.1
	 */
	public function setEmptySemanticData() {
		$this->setSemanticData( new SemanticData( DIWikiPage::newFromTitle( $this->title ) ) );
	}

	/**
	 * @deprecated since 2.1, use setEmptySemanticData
	 */
	public function clearData() {
		$this->setEmptySemanticData();
	}

	/**
	 * @deprecated since 2.1, use pushSemanticDataToParserOutput
	 */
	public function updateOutput() {
		$this->pushSemanticDataToParserOutput();
	}

	/**
	 * @since 2.1
	 *
	 * @param ParserOutput|null
	 */
	public function importFromParserOutput( ParserOutput $parserOutput = null ) {

		if ( $parserOutput === null ) {
			return;
		}

		$semanticData = $parserOutput->getExtensionData( self::DATA_ID );

		// Only import data that is known to be different
		if ( $semanticData !== null &&
			$this->getSubject()->equals( $semanticData->getSubject() ) &&
			$semanticData->getHash() !== $this->getSemanticData()->getHash() ) {

			$this->getSemanticData()->importDataFrom( $semanticData );
		}
	}

	/**
	 * @since 2.1
	 */
	public function pushSemanticDataToParserOutput() {

		// Ensure that errors are reported and recorded
		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$this->getSubject()
		);

		foreach ( $this->errors as $error ) {
			$processingErrorMsgHandler->addToSemanticData(
				$this->semanticData,
				$processingErrorMsgHandler->newErrorContainerFromMsg( $error )
			);
		}

		$this->setSemanticDataStateToParserOutputProperty();
		$this->parserOutput->setExtensionData( self::DATA_ID, $this->semanticData );
	}

	/**
	 * @since 2.1
	 */
	public function setSemanticDataStateToParserOutputProperty() {

		$this->parserOutput->setTimestamp( wfTimestampNow() );

		$this->parserOutput->setProperty(
			'smw-semanticdata-status',
			$this->semanticData->getProperties() !== array()
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isAnnotatedWithSemanticData() {
		return (bool)$this->parserOutput->getProperty( 'smw-semanticdata-status' );
	}

	/**
	 * @see SemanticData::addDataValue
	 *
	 * @since 1.9
	 *
	 * @param SMWDataValue $dataValue
	 */
	public function addDataValue( DataValue $dataValue ) {
		$this->semanticData->addDataValue( $dataValue );
	}

	/**
	 * Persistent marker to identify an update with a revision ID and allow
	 * to filter successive updates with that very same ID.
	 *
	 * @see LinksUpdateConstructed::process
	 *
	 * @since 3.0
	 *
	 * @param integer $rev
	 */
	public function markUpdate( $rev ) {
		$this->cache->save( smwfCacheKey( self::CACHE_NAMESPACE, $this->semanticData->getSubject()->getHash() ), $rev, 3600 );
	}

	/**
	 * @private This method is not for public use
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore( $enabledDeferredUpdate = false ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$latestRevID = $this->title->getLatestRevID( Title::GAID_FOR_UPDATE );

		if ( $this->skipUpdateOn( $latestRevID ) ) {

			$context = [
				'method' => __METHOD__,
				'role' => 'user',
				'revID' => $latestRevID
			];

			return $applicationFactory->getMediaWikiLogger()->info( 'Update (Found rev:{revID}, skip update)' );
		}

		$this->semanticData->setOption(
			Enum::OPT_SUSPEND_PURGE,
			$this->getOption( Enum::OPT_SUSPEND_PURGE )
		);

		$storeUpdater = $applicationFactory->newStoreUpdater(
			$this->semanticData
		);

		$storeUpdater->canCreateUpdateJob(
			$this->getOption( self::OPT_CREATE_UPDATE_JOB, true )
		);

		$storeUpdater->isChangeProp(
			$this->getOption( self::OPT_CHANGE_PROP_UPDATE )
		);

		DeferredCallableUpdate::releasePendingUpdates();

		$deferredTransactionalUpdate = $applicationFactory->newDeferredTransactionalUpdate( function() use( $storeUpdater ) {
			$storeUpdater->doUpdate();
		} );

		$deferredTransactionalUpdate->setOrigin(
			array(
				__METHOD__,
				$this->origin,
				$this->semanticData->getSubject()->getHash()
			)
		);

		$deferredTransactionalUpdate->enabledDeferredUpdate(
			$enabledDeferredUpdate
		);

		$deferredTransactionalUpdate->commitWithTransactionTicket();
		$deferredTransactionalUpdate->pushUpdate();

		return true;
	}

	/**
	 * @note ParserOutput::setLimitReportData
	 *
	 * @since 2.4
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addLimitReport( $key, $value ) {
		$this->parserOutput->setLimitReportData( 'smw-limitreport-' . $key, $value );
	}

	/**
	 * Setup the semantic data container either from the ParserOutput or
	 * if not available create an empty container
	 */
	private function initSemanticData() {

		$this->semanticData = $this->parserOutput->getExtensionData( self::DATA_ID );

		if ( !( $this->semanticData instanceof SemanticData ) ) {
			$this->setEmptySemanticData();
		}
	}

	private function skipUpdateOn( $rev ) {
		return $this->getOption( self::OPT_FORCED_UPDATE ) !== true &&
			$this->cache->fetch( smwfCacheKey( self::CACHE_NAMESPACE, $this->semanticData->getSubject()->getHash() ) ) === $rev;
	}

}
