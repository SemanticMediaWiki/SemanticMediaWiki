<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMW\NamespaceUriFinder;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class BrowseByProperty extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		$applicationFactory = ApplicationFactory::getInstance();

		$propertyListByApiRequest = new PropertyListByApiRequest(
			$applicationFactory->getStore(),
			$applicationFactory->getPropertySpecificationLookup()
		);

		$propertyListByApiRequest->setLimit(
			$params['limit']
		);

		$propertyListByApiRequest->setLanguageCode(
			$params['lang']
		);

		$propertyListByApiRequest->findPropertyListFor(
			$params['property']
		);

		foreach ( $propertyListByApiRequest->getNamespaces() as $ns ) {

			$uri = NamespaceUriFinder::getUri( $ns );

			if ( !$uri ) {
				continue;
			}

			$this->getResult()->addValue(
				null,
				'xmlns:' . $ns,
				$uri
			);
		}

		$data = $propertyListByApiRequest->getPropertyList();

		// I'm without words for this utter nonsense introduced here
		// because property keys can have a underscore _MDAT or for that matter
		// any other data field can
		// https://www.mediawiki.org/wiki/API:JSON_version_2
		// " ... can indicate that a property beginning with an underscore is not metadata using"
		if ( method_exists( $this->getResult(), 'setPreserveKeysList') ) {
			$this->getResult()->setPreserveKeysList(
				$data,
				array_keys( $data )
			);
		}

		$this->getResult()->addValue(
			null,
			'query',
			$data
		);

		$this->getResult()->addValue(
			null,
			'version',
			0.2
		);

		$this->getResult()->addValue(
			null,
			'query-continue-offset',
			$propertyListByApiRequest->getContinueOffset()
		);

		$this->getResult()->addValue(
			null,
			'meta',
			$propertyListByApiRequest->getMeta()
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
			'property' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_REQUIRED => false,
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => 50,
				ApiBase::PARAM_REQUIRED => false,
			),
			'lang' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_REQUIRED => false,
			)
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
			'property' => 'To select a specific property',
			'limit' => 'To specify the size of the list request'
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
			'API module to query a property list or an individual property.'
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
			'api.php?action=browsebyproperty&property=Modification_date',
			'api.php?action=browsebyproperty&limit=50',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . SMW_VERSION;
	}

}
