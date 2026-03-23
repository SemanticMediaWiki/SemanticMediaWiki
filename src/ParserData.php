<?php

namespace SMW;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Psr\Log\LoggerAwareTrait;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValues\DataValue;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Handling semantic data exchange with a ParserOutput object
 *
 * Provides access to a semantic data container that is generated
 * either from the ParserOutput or is a newly created container
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ParserData {

	use LoggerAwareTrait;

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

	private Title $title;

	private ParserOutput $parserOutput;

	private ?ParserOptions $parserOptions = null;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	private array $errors = [];

	/**
	 * @var
	 */
	private bool $canCreateUpdateJob = true;

	/**
	 * Identifies the origin of a request.
	 *
	 * @var string
	 */
	private $origin = '';

	private ?Options $options = null;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public function __construct( Title $title, ParserOutput $parserOutput ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
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
	public function setOption( $key, $value ): void {
		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ): void {
		$this->origin = $origin;
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle(): Title {
		return $this->title;
	}

	/**
	 * @since 1.9
	 *
	 * @return WikiPage
	 */
	public function getSubject(): WikiPage {
		return $this->getSemanticData()->getSubject();
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getOutput(): ParserOutput {
		return $this->parserOutput;
	}

	/**
	 * @since 3.0
	 *
	 * @param ParserOptions $parserOptions
	 */
	public function setParserOptions( ParserOptions $parserOptions ): void {
		$this->parserOptions = $parserOptions;
	}

	/**
	 * @since 3.0
	 *
	 * @return ParserOptions|null
	 */
	public function addExtraParserKey( $key ): void {
		$keysToCache = ApplicationFactory::getInstance()->getSettings()->get( 'smwgSetParserCacheKeys' ) ?? [];

		if ( in_array( $key, $keysToCache ) ) {
			// Looks odd in 1.30 "Saved in parser cache ... idhash:19989-0!canonical!userlang!dateformat!userlang!dateformat!userlang!dateformat!userlang!dateformat and ..."
			// therefore use the ParserOutput::recordOption instead
			$this->parserOutput->recordOption( $key );
		} elseif ( $this->parserOptions !== null ) {
			$this->parserOptions->addExtraKey( $key );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function isBlocked(): bool {
		return $this->hasAnnotationBlock();
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasAnnotationBlock(): bool {
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
	 * @return bool
	 */
	public function canUse(): bool {
		return !$this->hasAnnotationBlock();
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since  1.9
	 */
	public function addError( $error ): void {
		$this->errors = array_merge( $this->errors, (array)$error );
	}

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData ): void {
		$this->semanticData = $semanticData;
	}

	/**
	 * @deprecated since 2.0, use setSemanticData
	 */
	public function setData( SemanticData $semanticData ): void {
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
	public function setEmptySemanticData(): void {
		$this->setSemanticData( new SemanticData( WikiPage::newFromTitle( $this->title ) ) );
	}

	/**
	 * @since 2.1
	 *
	 * @param ParserOutput|null
	 */
	public function importFromParserOutput( ?ParserOutput $parserOutput = null ): void {
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
	 * @since 3.0
	 */
	public function copyToParserOutput(): void {
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

		$this->markParserOutput();
		$this->parserOutput->setExtensionData( self::DATA_ID, $this->semanticData );
	}

	/**
	 * @deprecated since 3.0, use copyToParserOutput
	 */
	public function pushSemanticDataToParserOutput(): void {
		$this->copyToParserOutput();
	}

	/**
	 * @since 3.0
	 */
	public function markParserOutput(): void {
		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgSetParserCacheTimestamp' ) ) {
			$this->parserOutput->setRevisionTimestamp( wfTimestampNow() );
		}

		$this->parserOutput->setExtensionData(
			'smw-semanticdata-status',
			$this->semanticData->getProperties() !== []
		);
	}

	/**
	 * @deprecated since 3.0, use pushSemanticDataToParserOutput
	 */
	public function setSemanticDataStateToParserOutputProperty(): void {
		$this->markParserOutput();
	}

	/**
	 * @since 2.5
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public static function hasSemanticData( ParserOutput $parserOutput ) {
		return $parserOutput->getExtensionData( 'smw-semanticdata-status' ) ?? false;
	}

	/**
	 * @see SemanticData::addDataValue
	 *
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 */
	public function addDataValue( DataValue $dataValue ): void {
		$this->semanticData->addDataValue( $dataValue );
	}

	/**
	 * @private This method is not for public use
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function updateStore( $opts = [] ): bool {
		$isDeferrableUpdate = false;

		// @legacy
		if ( $opts === true ) {
			$isDeferrableUpdate = true;
		}

		if ( isset( $opts['defer'] ) && $opts['defer'] ) {
			$isDeferrableUpdate = true;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$this->semanticData->setOption(
			Enum::OPT_SUSPEND_PURGE,
			$this->getOption( Enum::OPT_SUSPEND_PURGE )
		);

		$dataUpdater = $applicationFactory->newDataUpdater(
			$this->semanticData
		);

		$latestRevID = null;

		if (
			$this->getOption( self::OPT_FORCED_UPDATE, false ) === false &&
			$dataUpdater->isSkippable( $this->title, $latestRevID ) ) {

			$this->logger->info(
				[ 'Update', 'Skipping update', 'Found revision', '{revID}' ],
				[ 'role' => 'user', 'revID' => $latestRevID ]
			);

			return false;
		}

		$dataUpdater->canCreateUpdateJob(
			$this->getOption( self::OPT_CREATE_UPDATE_JOB, true )
		);

		$dataUpdater->isChangeProp(
			$this->getOption( self::OPT_CHANGE_PROP_UPDATE )
		);

		$dataUpdater->isDeferrableUpdate(
			$isDeferrableUpdate
		);

		$dataUpdater->setOrigin(
			$this->origin
		);

		$dataUpdater->doUpdate();

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
	public function addLimitReport( $key, $value ): void {
		$this->parserOutput->setLimitReportData( 'smw-limitreport-' . $key, $value );
	}

	/**
	 * Setup the semantic data container either from the ParserOutput or
	 * if not available create an empty container
	 */
	private function initSemanticData(): void {
		$this->semanticData = $this->parserOutput->getExtensionData( self::DATA_ID );

		if ( !( $this->semanticData instanceof SemanticData ) ) {
			$this->setEmptySemanticData();
		}
	}

}
