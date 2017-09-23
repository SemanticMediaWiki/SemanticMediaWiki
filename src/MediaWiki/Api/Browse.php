<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Api\Browse\ListLookup;
use SMW\MediaWiki\Api\Browse\ListAugmentor;
use SMW\MediaWiki\Api\Browse\LookupCache;

/**
 * Module to support selected browse activties including:
 *
 * - Search a list of available
 *   - categories
 *   - properties
 *   - concepts
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Browse extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		$parameters = json_decode( $params['params'], true );
		$res = [];

		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $parameters ) ) {

			// 1.29+
			if ( method_exists($this, 'dieWithError' ) ) {
				$this->dieWithError( [ 'smw-api-smwbrowse-invalid-parameters' ] );
			} else {
				$this->dieUsageMsg( 'smw-api-smwbrowse-invalid-parameters' );
			}
		}

		if ( $params['browse'] === 'category' ) {
			$res = $this->callListLookup( NS_CATEGORY, $parameters );
		}

		if ( $params['browse'] === 'property' ) {
			$res = $this->callListLookup( SMW_NS_PROPERTY, $parameters );
		}

		if ( $params['browse'] === 'concept' ) {
			$res = $this->callListLookup( SMW_NS_CONCEPT, $parameters );
		}

		$result = $this->getResult();

		foreach ( $res as $key => $value ) {

			if ( $key === 'query' ) {

				// For those items that start with _xyz as in _MDAT
				// https://www.mediawiki.org/wiki/API:JSON_version_2
				// " ... can indicate that a property beginning with an underscore ..."
				foreach ( $value as $k => $v ) {
					if ( $k{0} === '_' ) {
						$result->addPreserveKeysList( 'query', $k );
					}
				}
			}

			$result->addValue( null, $key, $value );
		}
	}

	private function callListLookup( $ns, $parameters ) {

		$applicationFactory = ApplicationFactory::getInstance();

		// We explicitly want the SQLStore here to avoid
		// "Call to undefined method SMW\SPARQLStore\SPARQLStore::getSQLOptions() ..."
		// since we don't use those methods anywher else other than the SQLStore
		$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );

		$listAugmentor = new ListAugmentor(
			$store
		);

		$listLookup = new ListLookup(
			$store,
			$listAugmentor
		);

		$lookupCache = new LookupCache(
			$applicationFactory->getCache(),
			$listLookup
		);

		return $lookupCache->lookup(
			$ns,
			$parameters
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'browse' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => array(
					'category',
					'property',
					'concept'
				)
			),
			'params' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'browse' => 'Specifies type of browse activty',
			'params' => 'JSON encoded parameters that matches the selected type requirment'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return array(
			'Semantic MediaWiki API module to support browse activties.'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date" }',
			'api.php?action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true }',
			'api.php?action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true, "prefLabel": true }',
			'api.php?action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true, "prefLabel": true, "usageCount": true }',
			'api.php?action=smwbrowse&browse=category&params={ "limit": 10, "offset": 0 }',
			'api.php?action=smwbrowse&browse=concept&params={ "limit": 10, "offset": 0 }'
		);
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

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.semantic-mediawiki.org/wiki/Help:API';
	}

}
