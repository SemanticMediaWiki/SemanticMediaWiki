<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use Iterator;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Enum;
use SMWQueryProcessor as QueryProcessor;
use SMWQuery as Query;

/**
 * Module to support various tasks initiate using the API interface
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Task extends ApiBase {

	const CACHE_NAMESPACE = 'smw:api:task';

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( $key ) {
		return smwfCacheKey( self::CACHE_NAMESPACE, [ $key ] );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$parameters = json_decode(
			$params['params'],
			true
		);

		$results = [];

		if ( $params['task'] === 'update' ) {
			$results = $this->callUpdateTask( $parameters );
		}

		if ( $params['task'] === 'check-query' ) {
			$results = $this->callCheckQueryTask( $parameters );
		}

		if ( $params['task'] === 'duplookup' ) {
			$results = $this->callDupLookupTask( $parameters );
		}

		if ( $params['task'] === 'job' ) {
			$results = $this->callGenericJobTask( $parameters );
		}

		if ( $params['task'] === 'run-joblist' ) {
			$results = $this->callJobListTask( $parameters );
		}

		$this->getResult()->addValue(
			null,
			'task',
			$results
		);
	}

	private function callDupLookupTask( $parameters ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$cache = $applicationFactory->getCache();

		$cacheUsage = $applicationFactory->getSettings()->get(
			'smwgCacheUsage'
		);

		$cacheTTL = 3600;

		if ( isset( $cacheUsage['api.task'] ) ) {
			$cacheTTL = $cacheUsage['api.task'];
		}

		$key = self::makeCacheKey( 'duplookup' );

		// Guard against repeated API calls (or fuzzing)
		if ( ( $result = $cache->fetch( $key ) ) !== false && $cacheTTL !== false ) {
			return $result + ['isFromCache' => true ];
		}

		$rows = $applicationFactory->getStore()->getObjectIds()->findDuplicates();

		// Avoid "Exception caught: Serialization of 'Closure' is not allowedException ..."
		if ( $rows instanceof Iterator ) {
			$rows = iterator_to_array( $rows );
		}

		$result = [
			'list' => $rows,
			'count' => count( $rows ),
			'time' => time()
		];

		$cache->save( $key, $result, $cacheTTL );

		return $result;
	}

	private function callCheckQueryTask( $parameters ) {

		if ( $parameters['subject'] === '' || $parameters['query'] === '' ) {
			return [ 'done' => false ];
		}

		$store = ApplicationFactory::getInstance()->getStore();

		$subject = DIWikiPage::doUnserialize(
			$parameters['subject']
		);

		foreach ( $parameters['query'] as $hash => $raw_query ) {

			// @see PostProcHandler::addQuery
			list( $query_hash, $result_hash ) = explode( '#', $hash );

			// Doesn't influence the fingerprint (aka query cache) so just
			// ignored it
			$printouts = [];
			$parameters = $raw_query['parameters'];

			if ( isset( $parameters['sortkeys']  ) ) {
				$order = [];
				$sort = [];

				foreach ( $parameters['sortkeys'] as $key => $order_by ) {
					$order[] = strtolower( $order_by );
					$sort[] = $key;
				}

				$parameters['sort'] = implode( ',', $sort );
				$parameters['order'] = implode( ',', $order );
			}

			QueryProcessor::addThisPrintout( $printouts, $parameters );

			$query = QueryProcessor::createQuery(
				$raw_query['conditions'],
				QueryProcessor::getProcessedParams( $parameters, $printouts ),
				QueryProcessor::INLINE_QUERY,
				'',
				$printouts
			);

			$query->setLimit(
				$parameters['limit']
			);

			$query->setOffset(
				$parameters['offset']
			);

			$query->setQueryMode(
				$parameters['querymode']
			);

			$query->setContextPage(
				$subject
			);

			$query->setOption( Query::PROC_CONTEXT, 'task.api' );

			$res = $store->getQueryResult(
				$query
			);

			// If the result_hash from before the post-edit and the result_hash
			// after the post-edit check are not the same then it means that the
			// list of entities changed hence send a `reload` command to the
			// API promise.
			if ( $result_hash !== $res->getHash( 'quick' ) ) {
				return [ 'done' => true, 'reload' => true ];
			}
		}

		return [ 'done' => true ];
	}

	private function callGenericJobTask( $params ) {

		$this->checkParameters( $params );

		if ( $params['subject'] === '' ) {
			return ['done' => false ];
		}

		$title = DIWikiPage::doUnserialize( $params['subject'] )->getTitle();

		if ( $title === null ) {
			return ['done' => false ];
		}

		if ( !isset( $params['job'] ) ) {
			return ['done' => false ];
		}

		$parameters = [];

		if ( isset( $params['parameters'] ) ) {
			$parameters = $params['parameters'];
		}

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();

		$job = $jobFactory->newByType(
			$params['job'],
			$title,
			$parameters
		);

		$job->insert();
	}

	private function callUpdateTask( $parameters ) {

		$this->checkParameters( $parameters );

		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize( $parameters['subject'] );
		$title = $subject->getTitle();
		$log = [];

		if ( $title === null ) {
			return ['done' => false ];
		}

		// Each single update is required to allow for a cascading computation
		// where one query follows another to ensure that results are updated
		// according to the value dependency of the referenced annotations that
		// rely on a computed (#ask) value
		if ( !isset( $parameters['ref'] ) ) {
			$parameters['ref'] = [ $subject->getHash() ];
		}

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();
		$isPost = isset( $parameters['post'] ) ? $parameters['post'] : false;
		$origin = [];

		if ( isset( $parameters['origin'] ) ) {
			$origin = [ 'origin' => $parameters['origin'] ];
		}

		foreach ( $parameters['ref'] as $ref ) {
			$updateJob = $jobFactory->newUpdateJob(
				$title,
				[
					UpdateJob::FORCED_UPDATE => true,
					Enum::OPT_SUSPEND_PURGE => false,
					'ref' => $ref
				] + $origin
			);

			if ( $isPost ) {
				$updateJob->insert();
			} else {
				$updateJob->run();
			}
		}

		return [ 'done' => true, 'log' => $log ];
	}

	private function callJobListTask( $parameters ) {

		$this->checkParameters( $parameters );

		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize( $parameters['subject'] );
		$title = $subject->getTitle();

		if ( $title === null ) {
			return [ 'done' => false ];
		}

		$jobQueue = ApplicationFactory::getInstance()->getJobQueue();
		$jobList = [];

		if ( isset( $parameters['jobs'] ) ) {
			$jobList = $parameters['jobs'];
		}

		$log = $jobQueue->runFromQueue(
			$jobList
		);

		return [ 'done' => true, 'log' => $log ];
	}

	private function checkParameters( $parameters ) {
		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $parameters ) ) {

			// 1.29+
			if ( method_exists( $this, 'dieWithError' ) ) {
				$this->dieWithError( [ 'smw-api-invalid-parameters' ] );
			} else {
				$this->dieUsageMsg( 'smw-api-invalid-parameters' );
			}
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'task' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => [

					// Run update using the updateJob
					'update',

					// Run a query check
					'check-query',

					// Duplicate lookup support
					'duplookup',

					// Insert/run a job
					'job',

					// Run jobs from a list directly without the job scheduler
					'run-joblist'
				]
			],
			'params' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			],
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return [
			'task' => 'Defines the task type',
			'params' => 'JSON encoded parameters that matches the selected type requirement'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return [
			'Semantic MediaWiki API module to invoke and execute tasks (for internal use only)'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::needsToken
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::mustBePosted
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return [
			'api.php?action=smwtask&task=update&params={ "subject": "Foo" }',
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ':' . SMW_VERSION;
	}

}
