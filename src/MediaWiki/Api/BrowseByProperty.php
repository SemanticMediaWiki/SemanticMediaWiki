<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use SMW\Localizer\Localizer;
use SMW\NamespaceUriFinder;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class BrowseByProperty extends ApiBase {

	/**
	 * #2696
	 * @deprecated since 3.0, use the smwbrowse API module
	 */
	public function isDeprecated() {
		return true;
	}

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

		$propertyListByApiRequest->setListOnly(
			$params['listonly']
		);

		if ( ( $lang = $params['lang'] ) === null ) {
			$lang = Localizer::getInstance()->getUserLanguage()->getCode();
		}

		$propertyListByApiRequest->setLanguageCode(
			$lang
		);

		$propertyListByApiRequest->findPropertyListBy(
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
		if ( method_exists( $this->getResult(), 'setPreserveKeysList' ) ) {
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
			2
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
		return [
			'property' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'limit' => [
				ParamValidator::PARAM_TYPE => 'limit',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => 50,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'lang' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'listonly' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_REQUIRED => false,
			]
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
			'property' => 'To match a specific property',
			'limit'    => 'To specify the size of the list request',
			'lang'     => 'To specify a specific language used for some attributes (description etc.)',
			'listonly' => 'To specify that only a property list is returned without further details'
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
			'API module to query a property list or an individual property.'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	public function getExamples() {
		return [
			'api.php?action=browsebyproperty&property=Modification_date',
			'api.php?action=browsebyproperty&limit=50',
			'api.php?action=browsebyproperty&limit=5&listonly=true',
		];
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
