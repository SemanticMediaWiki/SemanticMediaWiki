<?php

namespace SMW\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\DataItemHandlers\DIBlobHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIBooleanHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIConceptHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIGeoCoordinateHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DINumberHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DITimeHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataItemHandlerFactory {

	/**
	 * @var SQLStore
	*/
	private $store;

	/**
	 * @var array
	*/
	private $handlers = [];

	/**
	 * @var integer
	*/
	private $fieldTypeFeatures = false;

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $fieldTypeFeatures
	 */
	public function setFieldTypeFeatures( $fieldTypeFeatures ) {
		$this->fieldTypeFeatures = $fieldTypeFeatures;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return DIHandler
	 * @throws RuntimeException
	 */
	public function getHandlerByType( $type ) {

		if ( !isset( $this->handlers[$type] ) ) {
			$this->handlers[$type] = $this->newHandlerByType( $type );
		}

		return $this->handlers[$type];
	}

	private function newHandlerByType( $type ) {

		switch ( $type ) {
			case DataItem::TYPE_NUMBER:
				$handler = new DINumberHandler( $this->store );
				break;
			case DataItem::TYPE_BLOB:
				$handler = new DIBlobHandler( $this->store );
				break;
			case DataItem::TYPE_BOOLEAN:
				$handler = new DIBooleanHandler( $this->store );
				break;
			case DataItem::TYPE_URI:
				$handler = new DIUriHandler( $this->store );
				break;
			case DataItem::TYPE_TIME:
				$handler = new DITimeHandler( $this->store );
				break;
			case DataItem::TYPE_GEO:
				$handler = new DIGeoCoordinateHandler( $this->store );
				break;
			case DataItem::TYPE_WIKIPAGE:
				$handler = new DIWikiPageHandler( $this->store );
				break;
			case DataItem::TYPE_CONCEPT:
				$handler = new DIConceptHandler( $this->store );
				break;
			case DataItem::TYPE_PROPERTY:
				throw new DataItemHandlerException( "There is no DI handler for DataItem::TYPE_PROPERTY." );
			case DataItem::TYPE_CONTAINER:
				throw new DataItemHandlerException( "There is no DI handler for DataItem::TYPE_CONTAINER." );
			case DataItem::TYPE_ERROR:
				throw new DataItemHandlerException( "There is no DI handler for DataItem::TYPE_ERROR." );
			default:
				throw new DataItemHandlerException( "The value \"$type\" is not a valid dataitem ID." );
		}

		$handler->setFieldTypeFeatures(
			$this->fieldTypeFeatures
		);

		return $handler;
	}

}
