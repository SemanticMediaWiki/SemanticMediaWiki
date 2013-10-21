<?php

namespace SMW;

use Title;

/**
 * Api module to browse a subject
 *
 * @ingroup Api
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApiBrowse extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$serialized = SerializerFactory::serialize(
			$this->getSemanticData( $this->getSubject( $params['subject'] ) )
		);

		$this->getResult()->addValue( null, 'query', $this->runFormatter( $serialized ) );
	}

	/**
	 * @since 1.9
	 */
	protected function getSubject( $text ) {
		return DIWikiPage::newFromTitle( $this->newFromText( $text ) );
	}

	/**
	 * @codeCoverageIgnore
	 * @since 1.9
	 */
	protected function newFromText( $text ) {

		$title = Title::newFromText( $text );

		if ( $title instanceOf Title ) {
			return $title;
		}

		$this->dieUsageMsg( array( 'invalidtitle', $text ) );
	}

	/**
	 * @since 1.9
	 */
	protected function getSemanticData( DIWikiPage $subject ) {

		$store = $this->withContext()->getStore();
		$semanticData = $store->getSemanticData( $subject );

		foreach ( $semanticData->getProperties() as $property ) {
			if ( $property->getKey() === DIProperty::TYPE_SUBOBJECT || $property->getKey() === DIProperty::TYPE_ASKQUERY ) {
				$this->addSubSemanticData( $store, $property, $semanticData );
			}
		}

		return $semanticData;
	}

	/**
	 * @note In case where the original SemanticData container does not include
	 * subobjects, this method will add them to ensure a "complete object" for
	 * all available entities that belong to this subject (excluding incoming
	 * properties)
	 *
	 * @note If the subobject already exists within the current SemanticData
	 * instance it will not be imported again (this avoids calling the Store
	 * again)
	 *
	 * @since 1.9
	 */
	protected function addSubSemanticData( $store, $property, &$semanticData ) {

		$subSemanticData = $semanticData->getSubSemanticData();

		foreach ( $semanticData->getPropertyValues( $property ) as $value ) {
			if ( $value instanceOf DIWikiPage && !isset( $subSemanticData[ $value->getSubobjectName() ] ) ) {
				$semanticData->addSubSemanticData( $store->getSemanticData( $value ) );
			}
		}
	}

	/**
	 * @since 1.9
	 */
	protected function runFormatter( $serialized ) {

		$this->addIndexTags( $serialized );

		if ( isset( $serialized['sobj'] ) ) {

			$this->getResult()->setIndexedTagName( $serialized['sobj'], 'subobject' );

			foreach ( $serialized['sobj'] as $key => &$value ) {
				$this->addIndexTags( $value );
			}
		}

		return $serialized;
	}

	/**
	 * @since 1.9
	 */
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
			'api.php?action=browse&subject=Main_Page',
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
