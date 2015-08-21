<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
interface ValueLookupStore {

	/**
	 * @see Store::getSemanticData
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false );

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getProperties( DIWikiPage $subject, $requestOptions = null );

	/**
	 * @see Store::getPropertyValues
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, $requestOptions = null );

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, $requestOptions = null );

}
