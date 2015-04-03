<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;

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
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$applicationFactory = ApplicationFactory::getInstance();

		$title = $applicationFactory
			->newTitleCreator()
			->createFromText( $params['subject'] );

		$deepRedirectTargetResolver = $applicationFactory
			->newMwCollaboratorFactory()
			->newDeepRedirectTargetResolver();

		try {
			$title = $deepRedirectTargetResolver->findRedirectTargetFor( $title );
		} catch ( \Exception $e ) {
			$this->dieUsage( $e->getMessage(), 'redirect-target-unresolvable'  );
		}

		$dataItem = new DIWikiPage(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$params['subobject']
		);

		$semanticData = $applicationFactory
			->getStore()
			->getSemanticData( $dataItem );

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		$this->getResult()->addValue(
			null,
			'query',
			$this->doFormat( $semanticDataSerializer->serialize( $semanticData ) )
		);
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
		return array(
			'subject' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_REQUIRED => true,
			),
			'subobject' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
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
			'subject' => 'The subject to be queried',
			'subobject' => 'A particular subobject id for the related subject'
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
			'API module to query a subject.'
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
			'api.php?action=browsebysubject&subject=Main_Page',
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
