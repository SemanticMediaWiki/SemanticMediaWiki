<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;

/**
 * Browse a subject api module
 *
 * @note To browse a particular subobject use the 'subobject' parameter because
 * MW's WebRequest (responsible for handling request data sent by a browser) will
 * eliminate any fragments (marked by "#") therefore using something like
 * '"Lorem_ipsum#Foo' is not going to work but '&subject=Lorem_ipsum&subobject=Foo'
 * will return results for the selected subobject
 *
 * @ingroup Api
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BrowseBySubject extends ApiBase {

	/**
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

		if ( isset( $params['type'] ) && $params['type'] === 'html' ) {
			$data = $this->buildHTML( $params );
		} else {
			$data = $this->doSerialize( $params );
		}

		$this->getResult()->addValue(
			null,
			'query',
			$data
		);
	}

	protected function buildHTML( $params ) {

		$subject = new DIWikiPage(
			$params['subject'],
			$params['ns'],
			$params['iw'],
			$params['subobject']
		);

		$htmlBuilder = new HtmlBuilder(
			ApplicationFactory::getInstance()->getStore(),
			$subject
		);

		$htmlBuilder->setOptions(
			(array)$params['options']
		);

		return $htmlBuilder->buildHTML();
	}

	protected function doSerialize( $params ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$title = $applicationFactory->newTitleFactory()->newFromText(
			$params['subject'],
			$params['ns']
		);

		$deepRedirectTargetResolver = $applicationFactory->newMwCollaboratorFactory()->newDeepRedirectTargetResolver();

		try {
			$title = $deepRedirectTargetResolver->findRedirectTargetFor( $title );
		} catch ( \Exception $e ) {

			// 1.29+
			if ( method_exists( $this, 'dieWithError' ) ) {
				$this->dieWithError( [ 'smw-redirect-target-unresolvable', $e->getMessage() ] );
			} else {
				$this->dieUsage( $e->getMessage(), 'redirect-target-unresolvable'  );
			}
		}

		$dataItem = new DIWikiPage(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$params['subobject']
		);

		$semanticData = $applicationFactory->getStore()->getSemanticData(
			$dataItem
		);

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		return $this->doFormat( $semanticDataSerializer->serialize( $semanticData ) );
	}

	protected function doFormat( $serialized ) {

		$this->addIndexTags( $serialized );

		if ( isset( $serialized['sobj'] ) ) {

			$this->getResult()->setIndexedTagName( $serialized['sobj'], 'subobject' );

			foreach ( $serialized['sobj'] as $key => &$value ) {
				$this->addIndexTags( $value );
			}
		}

		return $serialized;
	}

	protected function addIndexTags( &$serialized ) {

		if ( isset( $serialized['data'] ) && is_array( $serialized['data'] ) ) {

			$this->getResult()->setIndexedTagName( $serialized['data'], 'property' );

			foreach ( $serialized['data'] as $key => $value ) {
				if ( isset( $serialized['data'][$key]['dataitem'] ) && is_array( $serialized['data'][$key]['dataitem'] ) ) {
					$this->getResult()->setIndexedTagName( $serialized['data'][$key]['dataitem'], 'value' );
				}
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
			'subject' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_REQUIRED => true,
			],
			'ns' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_REQUIRED => false,
			],
			'iw' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			],
			'subobject' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			],
			'type' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			],
			'options' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
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
			'subject' => 'The subject to be queried',
			'subobject' => 'A particular subobject id for the related subject'
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
			'API module to query a subject.'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return [
			'api.php?action=browsebysubject&subject=Main_Page',
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
