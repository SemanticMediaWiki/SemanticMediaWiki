<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface PluggableDataType {

	/**
	 * Returns the type_id and is by convention for an third-party extension
	 * indicated by two underscores such as `__some_type`.
	 *
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getTypeId();

	/**
	 * Returns the class name or callable associated with the type_id.
	 *
	 * @since 3.1
	 *
	 * @return string|callable
	 */
	public function getClass();

	/**
	 * Returns the DataItem::TYPE_...
	 *
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getItemType();

	/**
	 * Returns a label or false for types that cannot be accessed by users.
	 *
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getLabel();

	/**
	 * Returns possible aliases for a type
	 *
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getAliases();

	/**
	 * Returns whether it is a sub type (container, subobject etc.) or not
	 *
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function isSubType();

	/**
	 * Returns whether the type is browseable or not
	 *
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function isBrowsableType();

	/**
	 * Allows to return additional callable and accessible callbacks on an
	 * individual DataVaue of that type.
	 *
	 * The expected form is:
	 * [ 'some.key' => callable ]
	 *
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getCallables();

}
