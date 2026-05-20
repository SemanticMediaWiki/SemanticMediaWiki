<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use SMW\MediaWiki\JobQueue;
use SMW\Site;
use SMW\Store;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to obtain info about the SMW install, primarily targeted at
 * usage by the SMW registry.
 *
 * @ingroup Api
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Info extends ApiBase {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly Store $store,
		private readonly JobQueue $jobQueue
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$requestedInfo = $params['info'];

		$map = [];
		$semanticStats = [];

		if ( in_array( 'propcount', $requestedInfo )
			|| in_array( 'jobcount', $requestedInfo )
			|| in_array( 'errorcount', $requestedInfo )
			|| in_array( 'deletecount', $requestedInfo )
			|| in_array( 'totalpropcount', $requestedInfo )
			|| in_array( 'usedpropcount', $requestedInfo )
			|| in_array( 'proppagecount', $requestedInfo )
			|| in_array( 'querycount', $requestedInfo )
			|| in_array( 'querysize', $requestedInfo )
			|| in_array( 'formatcount', $requestedInfo )
			|| in_array( 'conceptcount', $requestedInfo )
			|| in_array( 'subobjectcount', $requestedInfo )
			|| in_array( 'declaredpropcount', $requestedInfo ) ) {

			$semanticStats = $this->store->getStatistics();

			$map = [
				'propcount' => 'PROPUSES',
				'errorcount' => 'ERRORUSES',
				'deletecount' => 'DELETECOUNT',
				'usedpropcount' => 'USEDPROPS',
				'totalpropcount' => 'TOTALPROPS',
				'declaredpropcount' => 'DECLPROPS',
				'proppagecount' => 'OWNPAGE',
				'querycount' => 'QUERY',
				'querysize' => 'QUERYSIZE',
				'conceptcount' => 'CONCEPTS',
				'subobjectcount' => 'SUBOBJECTS',
			];
		}

		$this->getResult()->addValue(
			null,
			'info',
			$this->doMapResultInfoFrom( $map, $requestedInfo, $semanticStats )
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'info' => [
				ParamValidator::PARAM_DEFAULT => 'propcount|usedpropcount|declaredpropcount',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'propcount',
					'errorcount',
					'deletecount',
					'usedpropcount',
					'totalpropcount',
					'declaredpropcount',
					'proppagecount',
					'querycount',
					'querysize',
					'formatcount',
					'conceptcount',
					'subobjectcount',
					'jobcount'
				],
				ApiBase::PARAM_HELP_MSG => 'apihelp-smwinfo-param-info',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=smwinfo&info=proppagecount|propcount'
				=> 'apihelp-smwinfo-example-1',
		];
	}

	/**
	 * @return mixed[]
	 */
	private function doMapResultInfoFrom( array $map, $requestedInfo, array $semanticStats ): array {
		$resultInfo = [];

		foreach ( $map as $apiName => $smwName ) {
			if ( in_array( $apiName, $requestedInfo ) ) {
				$resultInfo[$apiName] = $semanticStats[$smwName];
			}
		}

		if ( in_array( 'formatcount', $requestedInfo ) ) {
			$resultInfo['formatcount'] = [];

			foreach ( $semanticStats['QUERYFORMATS'] as $name => $count ) {
				$resultInfo['formatcount'][$name] = $count;
			}
		}

		if ( in_array( 'jobcount', $requestedInfo ) ) {
			$resultInfo['jobcount'] = [];

			foreach ( Site::getJobClasses( 'SMW' ) as $type => $class ) {
				$size = $this->jobQueue->getQueueSize( $type );

				if ( $size > 0 ) {
					$resultInfo['jobcount'][$type] = $size;
				}
			}
		}

		return $resultInfo;
	}

}
