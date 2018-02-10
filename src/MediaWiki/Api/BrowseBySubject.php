<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\ContentsBuilder;

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

		if ( isset( $params['type'] ) && $params['type'] === 'html' ) {
			$data = $this->getHtmlFormat( $params );
		} else {
			$data = $this->getRawFormat( $params );
		}

		$this->getResult()->addValue(
			null,
			'query',
			$data
		);
	}

	protected function getHtmlFormat( $params ) {

		$subject = new DIWikiPage(
			$params['subject'],
			$params['ns'],
			$params['iw'],
			$params['subobject']
		);

		$contentsBuilder = new ContentsBuilder(
			ApplicationFactory::getInstance()->getStore(),
			$subject
		);

		$contentsBuilder->importOptionsFromJson( $params['options'] );

		return $contentsBuilder->getHtml();
	}

	protected function getRawFormat( $params ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$title = $applicationFactory->newTitleCreator()->createFromText(
			$params['subject']
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
		return array(
			'subject' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_REQUIRED => true,
			),
			'ns' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			),
			'iw' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			),
			'subobject' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			),
			'type' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => false,
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_REQUIRED => false,
			),
			'options' => array(
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
