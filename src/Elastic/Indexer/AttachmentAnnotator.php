<?php

namespace SMW\Elastic\Indexer;

use SMW\DataItemFactory;
use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWDITime as DITime;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class AttachmentAnnotator implements PropertyAnnotator {

	/**
	 * @var ContainerSemanticData
	 */
	private $containerSemanticData;

	/**
	 * @var []
	 */
	private $doc = [];

	/**
	 * @since 3.0
	 *
	 * @param ContainerSemanticData $containerSemanticData
	 * @param array $doc
	 */
	public function __construct( ContainerSemanticData $containerSemanticData, array $doc ) {
		$this->containerSemanticData = $containerSemanticData;
		$this->doc = $doc;
	}

	/**
	 * @since 3.0
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return new DIProperty( '_FILE_ATTCH' );
	}

	/**
	 * @since 3.0
	 *
	 * @return DIContainer
	 */
	public function getContainer() {
		return new DIContainer( $this->containerSemanticData );
	}

	/**
	 * @see PropertyAnnotator::getSemanticData
	 * @since 3.0
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->containerSemanticData;
	}

	/**
	 * @see PropertyAnnotator::addAnnotation
	 * @since 3.0
	 *
	 * @return PropertyAnnotator
	 */
	public function addAnnotation() {

		$dataItemFactory = new DataItemFactory();

		// @see https://www.elastic.co/guide/en/elasticsearch/plugins/master/using-ingest-attachment.html
		if ( isset( $this->doc['_source']['attachment']['date'] ) ) {
			if ( ( $dataItem = DITime::newFromTimestamp( $this->doc['_source']['attachment']['date'] ) ) instanceof DITime ) {
				$this->containerSemanticData->addPropertyObjectValue(
					$dataItemFactory->newDIProperty( '_CONT_DATE' ),
					$dataItem
				);
			}
		}

		if ( isset( $this->doc['_source']['attachment']['content_type'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_TYPE' ),
				$dataItemFactory->newDIBlob( $this->doc['_source']['attachment']['content_type'] )
			);
		}

		if ( isset( $this->doc['_source']['attachment']['author'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_AUTHOR' ),
				$dataItemFactory->newDIBlob( $this->doc['_source']['attachment']['author'] )
			);
		}

		if ( isset( $this->doc['_source']['attachment']['title'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_TITLE' ),
				$dataItemFactory->newDIBlob( $this->doc['_source']['attachment']['title'] )
			);
		}

		if ( isset( $this->doc['_source']['attachment']['language'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_LANG' ),
				$dataItemFactory->newDIBlob( $this->doc['_source']['attachment']['language'] )
			);
		}

		if ( isset( $this->doc['_source']['attachment']['content_length'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_LEN' ),
				$dataItemFactory->newDINumber( intval( $this->doc['_source']['attachment']['content_length'] ) )
			);
		}

		if ( isset( $this->doc['_source']['attachment']['keywords'] ) ) {
			$this->containerSemanticData->addPropertyObjectValue(
				$dataItemFactory->newDIProperty( '_CONT_KEYW' ),
				$dataItemFactory->newDINumber( intval( $this->doc['_source']['attachment']['keywords'] ) )
			);
		}

		return $this;
	}

}
