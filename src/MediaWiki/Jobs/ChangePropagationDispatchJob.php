<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use Title;
use SMW\RequestOptions;
use SMW\Utils\TempFile;
use SMWExporter as Exporter;
use SMW\SQLStore\ChangePropagationEntityFinder;
use SMW\ParserData;
use RuntimeException;

/**
 * `ChangePropagationDispatchJob` dispatches update jobs via `ChangePropagationUpdateJob`
 * to allow isolating the execution and count pending jobs without using an extra
 * tracking mechanism during an update process.
 *
 * `ChangePropagationUpdateJob` itself relies on the `UpdateJob` to get the update
 * being processed.
 *
 * `ChangePropagationDispatchJob` is responsible for:
 *
 * - Select entities that are being connected to a property specification
 *   change
 * - Once the selection process has been finalized, update the property with the
 *   new specification (which has been locked before this update)
 *
 * Due to the possibility that a large list of entities is connected to a
 * property and its change, an iterative or recursive processing is not viable
 * (as the changed specification should be available as soon as possible) therefore
 * the selection process will move the result of entities to chunked temp files
 * to avoid having to use a DB connection during the process (has been observed
 * during tests that would lead to an out-of-memory) to store a list of
 * entities that requires an update.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationDispatchJob extends JobBase {

	/**
	 * Size of rows stored in a temp file
	 */
	const CHUNK_SIZE = 1000;

	/**
	 * Temp marker namespace
	 */
	const CACHE_NAMESPACE = 'smw:chgprop';

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\ChangePropagationDispatchJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * Called from PropertyChangePropagationNotifier
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 * @param array $params
	 *
	 * @return boolean
	 */
	public static function planAsJob( DIWikiPage $subject, $params = array() ) {

		Exporter::getInstance()->resetCacheBy( $subject );
		ApplicationFactory::getInstance()->getPropertySpecificationLookup()->resetCacheBy(
			$subject
		);

		$changePropagationDispatchJob = new self( $subject->getTitle(), $params );
		$changePropagationDispatchJob->lazyPush();

		return true;
	}

	/**
	 * Called from PropertyTableIdReferenceDisposer
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 * @param array $params
	 *
	 * @return boolean
	 */
	public static function cleanUp( DIWikiPage $subject, $params = array() ) {

		$changePropagationDispatchJob = new self( $subject->getTitle(), $params );
		$changePropagationDispatchJob->findAndDispatch( false );

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 */
	public static function removeProcessMarker( DIWikiPage $subject ) {

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		ApplicationFactory::getInstance()->getCache()->delete(
			smwfCacheKey(
				self::CACHE_NAMESPACE,
				$subject->getHash()
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return boolean
	 */
	public static function hasPendingJobs( DIWikiPage $subject ) {

		if ( self::getQueueSize( 'SMW\ChangePropagationUpdateJob' ) > 0 ) {
			return true;
		}

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$subject->getHash()
		);

		return ApplicationFactory::getInstance()->getCache()->fetch( $key ) > 0;
	}

	/**
	 * Use as very simple heuristic to count pending jobs for the overall change
	 * propagation. The count will indicate any job related to the change propagation
	 * and does not distinguish by changes to a specific property.
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return integer
	 */
	public static function getPendingJobsCount( DIWikiPage $subject ) {

		$count = self::getQueueSize( 'SMW\ChangePropagationUpdateJob' );

		// Fallback for when JobQueueGroup::getSize doesn't yet contain the
		// updated stats
		if ( $count == 0 && self::hasPendingJobs( $subject ) ) {
			$key = smwfCacheKey(
				self::CACHE_NAMESPACE,
				$subject->getHash()
			);

			$count = ApplicationFactory::getInstance()->getCache()->fetch( $key );
		}

		return $count;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.0
	 */
	public function run() {

		$subject = DIWikiPage::newFromTitle( $this->getTitle() );

		if ( $this->hasParameter( 'dataFile' ) ) {
			return $this->dispatchFromFile( $subject, $this->getParameter( 'dataFile' ) );
		}

		$this->findAndDispatch( true );

		return true;
	}

	/**
	 * Called from PropertyTableIdReferenceDisposer
	 *
	 * @since 3.0
	 *
	 * @param boolean $commitSpecificationChange
	 */
	public function findAndDispatch( $commitSpecificationChange = true ) {

		if ( $this->getTitle()->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$subject = DIWikiPage::newFromTitle( $this->getTitle() );

		$applicationFactory = ApplicationFactory::getInstance();
		$iteratorFactory = $applicationFactory->getIteratorFactory();

		$property = DIProperty::newFromUserLabel(
			$this->getTitle()->getText()
		);

		$applicationFactory->getMediaWikiLogger()->info(
			'ChangePropagationDispatchJob on ' . $subject->getHash()
		);

		$changePropagationEntityFinder = new ChangePropagationEntityFinder(
			$applicationFactory->getStore(),
			$iteratorFactory
		);

		$changePropagationEntityFinder->isTypePropagation(
			$this->getParameter( 'isTypePropagation' )
		);

		$appendIterator = $changePropagationEntityFinder->findByProperty(
			$property
		);

		// Refresh the property page once more on the last dispatch
		$appendIterator->add(
			array( $subject )
		);

		// After relevant subjects has been selected, commit the changes to the
		// property so that the lock can be removed and any new specification
		// (type, allows values etc.) are available upon executing individual
		// jobs.
		$this->commitSpecificationChangePropagationAsJob(
			$subject,
			$appendIterator->count(),
			$commitSpecificationChange
		);

		$chunkedIterator = $iteratorFactory->newChunkedIterator(
			$appendIterator,
			self::CHUNK_SIZE
		);

		$i = 0;
		$tempFile = $applicationFactory->create( 'TempFile' );

		$file = $tempFile->generate(
			'smw_chgprop_',
			$subject->getHash(),
			uniqid()
		);

		foreach ( $chunkedIterator as $chunk ) {
			$this->pushChangePropagationDispatchJob( $tempFile, $file, $i++, $chunk );
		}
	}

	private function pushChangePropagationDispatchJob( $tempFile, $file, $num, $chunk ) {

		$data = array();
		$file .= "_$num.tmp";

		// Filter any subobject
		foreach ( $chunk as $val ) {
			$data[] = ( $val instanceof DIWikiPage ? $val->asBase()->getHash() : $val );
		}

		// Filter duplicates and write the temp file
		$tempFile->write(
			$file,
			implode( "\n", array_keys( array_flip( $data ) ) )
		);

		$checkSum = $tempFile->getCheckSum( $file );

		// Use the checkSum as verification method to avoid manipulation of the
		// contents by third-parties
		$changePropagationDispatchJob = new ChangePropagationDispatchJob(
			$this->getTitle(),
			array(
				'dataFile' => $file,
				'checkSum' => $checkSum
			) + self::newRootJobParams(
				"ChangePropagationDispatchJob:$file:$checkSum"
			)
		);

		$changePropagationDispatchJob->lazyPush();
	}

	private function dispatchFromFile( $subject, $file ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$property = DIProperty::newFromUserLabel(
			$this->getTitle()->getText()
		);

		$semanticData = $applicationFactory->getStore()->getSemanticData(
			$subject
		);

		$tempFile = $applicationFactory->create( 'TempFile' );

		$updateMarker = $applicationFactory->getCache()->fetch(
			smwfCacheKey( self::CACHE_NAMESPACE, $subject->getHash() )
		);

		// SemanticData hasn't been updated, re-enter the cycle to ensure that
		// the update of the property took place
		if ( $updateMarker === false ) {

			$params = $this->params;

			$changePropagationDispatchJob = new ChangePropagationDispatchJob(
				$this->getTitle(),
				$params
			);

			$changePropagationDispatchJob->insert();

			$applicationFactory->getMediaWikiLogger()->info(
				'ChangePropagationDispatchJob missing update marker, retry on ' . $subject->getHash()
			);

			return true;
		}

		$contents = $tempFile->read(
			$file,
			$this->getParameter( 'checkSum' )
		);

		// @see ChangePropagationDispatchJob::pushChangePropagationDispatchJob
		$dataItems = explode( "\n", $contents );

		$this->scheduleChangePropagationUpdateJobFromList(
			$dataItems
		);

		$tempFile->delete( $file );

		return true;
	}

	private function scheduleChangePropagationUpdateJobFromList( $dataItems ) {

		foreach ( $dataItems as $dataItem ) {

			if ( $dataItem === '' ) {
				continue;
			}

			$title = DIWikiPage::doUnserialize( $dataItem )->getTitle();

			$changePropagationUpdateJob = new ChangePropagationUpdateJob(
				$title,
				array(
					UpdateJob::FORCED_UPDATE => true
				)
			);

			$changePropagationUpdateJob->insert();
		}
	}

	private function commitSpecificationChangePropagationAsJob( $subject, $count, $commitSpecificationChange = true ) {

		if ( $commitSpecificationChange === false ) {
			return;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$connection = $applicationFactory->getStore()->getConnection( 'mw.db' );
		$transactionTicket = $connection->getEmptyTransactionTicket( __METHOD__ );

		$changePropagationUpdateJob = new ChangePropagationUpdateJob(
			$subject->getTitle(),
			array(
				UpdateJob::CHANGE_PROP => $subject->getSerialization(),
				UpdateJob::FORCED_UPDATE => true
			)
		);

		$changePropagationUpdateJob->run();

		// Make sure changes are committed before continuing processing
		$connection->commitAndWaitForReplication( __METHOD__, $transactionTicket );

		// Add temporary update marker
		// 24h ttl and it is expected that the JobQueue will run within this time
		// frame so that the JobQueueGroup::getSize can catch up with the update
		// marker.
		//
		// The marker will be removed after running the ChangePropagationUpdateJob
		// on the same subject.
		$applicationFactory->getCache()->save(
			smwfCacheKey( self::CACHE_NAMESPACE, $subject->getHash() ),
			$count,
			60 * 60 * 24
		);

		$applicationFactory->getPropertySpecificationLookup()->resetCacheBy( $subject );

		// Make sure the cache is reset in case runJobs.php --wait is used to avoid
		// reusing outdated type assignments
		$applicationFactory->getStore()->clear();
	}

}
