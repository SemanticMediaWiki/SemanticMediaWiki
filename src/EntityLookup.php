<?php

namespace SMW;

use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface EntityLookup {

	/**
	 * Retrieve all data stored about the given subject and return it as a
	 * SemanticData container. There are no options: it just returns all
	 * available data as shown in the page's Factbox.
	 * $filter is an array of strings that are datatype IDs. If given, the
	 * function will avoid any work that is not necessary if only
	 * properties of these types are of interest.
	 *
	 * @note There is no guarantee that the store does not retrieve more
	 * data than requested when a filter is used. Filtering just ensures
	 * that only necessary requests are made, i.e. it improves performance.
	 *
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions|string[]|bool $filter
	 *
	 * @return SemanticData
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false );

	/**
	 * Get an array of all properties for which the given subject has some
	 * value. The result is an array of DIProperty objects.
	 *
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DataItem[]|[]
	 */
	public function getProperties( DIWikiPage $subject, RequestOptions $requestOptions = null );

	/**
	 * Get an array of all property values stored for the given subject and
	 * property. The result is an array of DataItem objects.
	 *
	 * If called with $subject == null, all values for the given property
	 * are returned.
	 *
	 * @since 2.5
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DataItem[]|[]|Iterator
	 */
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, RequestOptions $requestOptions = null );

	/**
	 * Get an array of all subjects that have the given value for the given
	 * property. The result is an array of DIWikiPage objects. If null
	 * is given as a value, all subjects having that property are returned.
	 *
	 * @since 2.5
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DIWikiPage[]|[]|Iterator
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, RequestOptions $requestOptions = null );

	/**
	 * Get an array of all subjects that have some value for the given
	 * property. The result is an array of DIWikiPage objects.
	 *
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DIWikiPage[]|Iterator
	 */
	public function getAllPropertySubjects( DIProperty $property, RequestOptions $requestOptions = null );

	/**
	 * Get an array of all properties for which there is some subject that
	 * relates to the given value. The result is an array of DIWikiPage
	 * objects.
	 *
	 * @note In some stores, this function might be implemented partially
	 * so that only values of type Page (_wpg) are supported.
	 *
	 * @since 2.5
	 *
	 * @param DataItem $object
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DataItem[]|[]
	 */
	public function getInProperties( DataItem $object, RequestOptions $requestOptions = null );

}
