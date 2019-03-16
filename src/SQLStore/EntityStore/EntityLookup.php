<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\EntityLookup as IEntityLookup;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookup implements IEntityLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @see Store::getSemanticData
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {
		return $this->store->getReader()->getSemanticData( $subject, $filter );
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getProperties( DIWikiPage $subject, RequestOptions $requestOptions = null ) {
		return $this->store->getReader()->getProperties( $subject, $requestOptions );
	}

	/**
	 * @see Store::getPropertyValues
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, RequestOptions $requestOptions = null ) {
		return $this->store->getReader()->getPropertyValues( $subject, $property, $requestOptions );
	}

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, RequestOptions $requestOptions = null ) {
		return $this->store->getReader()->getPropertySubjects( $property, $dataItem, $requestOptions );
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getAllPropertySubjects( DIProperty $property, RequestOptions $requestOptions = null  ) {
		return $this->store->getReader()->getAllPropertySubjects( $property, $requestOptions );
	}

	/**
	 * @see Store::getInProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getInProperties( DataItem $object, RequestOptions $requestOptions = null ) {
		return $this->store->getReader()->getInProperties( $object, $requestOptions );
	}

}
