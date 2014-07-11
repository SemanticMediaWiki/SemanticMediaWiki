<?php

namespace SMW\MediaWiki\Api;

use SMW\Application;
use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;
use ApiBase;

/**
 * Browse a subject api module
 *
 * @note Browsing of a subobject subject is limited to its "parent" subject,
 * meaning that a request for a "Foo#_ed5a9979db6609b32733eda3fb747d21" subject
 * will produce information for "Foo" as a whole including its subobjects
 * because MW's WebRequest (responsible for handling request data sent by a
 * browser) will eliminate any fragments (indicated by "#") to an Api consumer
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
	 * @var Title
	 */
	private $title;

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$application = Application::getInstance();

		try {
			$this->title = $application->newTitleCreator()->createFromText( $params['subject'] )->findRedirect()->getTitle();
		} catch ( \Exception $e ) {
			$this->dieUsageMsg( array( 'invalidtitle', $this->title ) );
		}

		$semanticData = $application->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) );

		$this->getResult()->addValue(
			null,
			'query',
			$this->doFormat( $application->newSerializerFactory()->serialize( $semanticData ) )
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
				if ( isset( $serialized['data'][ $key ]['dataitem'] ) && is_array( $serialized['data'][ $key ]['dataitem'] ) ) {
					$this->getResult()->setIndexedTagName( $serialized['data'][ $key ]['dataitem'], 'value' );
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
