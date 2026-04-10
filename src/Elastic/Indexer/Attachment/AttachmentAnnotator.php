<?php

namespace SMW\Elastic\Indexer\Attachment;

use SMW\DataItemFactory;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataModel\ContainerSemanticData;
use SMW\Property\Annotator;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class AttachmentAnnotator implements Annotator {

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly ContainerSemanticData $containerSemanticData,
		private array $doc = [],
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @return Property
	 */
	public function getProperty(): Property {
		return new Property( '_FILE_ATTCH' );
	}

	/**
	 * @since 3.0
	 *
	 * @return Container
	 */
	public function getContainer(): Container {
		return new Container( $this->containerSemanticData );
	}

	/**
	 * @see Annotator::getSemanticData
	 * @since 3.0
	 *
	 * @return SemanticData
	 */
	public function getSemanticData(): ContainerSemanticData {
		return $this->containerSemanticData;
	}

	/**
	 * @see Annotator::addAnnotation
	 * @since 3.0
	 *
	 * @return Annotator
	 */
	public function addAnnotation(): static {
		$dataItemFactory = new DataItemFactory();

		// @see https://www.elastic.co/guide/en/elasticsearch/plugins/master/using-ingest-attachment.html
		if ( isset( $this->doc['_source']['attachment']['date'] ) ) {
			if ( ( $dataItem = Time::newFromTimestamp( $this->doc['_source']['attachment']['date'] ) ) instanceof Time ) {
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
				$dataItemFactory->newDIBlob( $this->doc['_source']['attachment']['keywords'] )
			);
		}

		return $this;
	}

}
