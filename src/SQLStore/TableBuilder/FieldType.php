<?php

namespace SMW\SQLStore\TableBuilder;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FieldType {

	/**
	 * @var string
	 */
	const FIELD_ID = 'id';

	/**
	 * @var string
	 */
	const FIELD_ID_PRIMARY = 'id primary';

	/**
	 * @var string
	 */
	const FIELD_TITLE = 'title';

	/**
	 * @var string
	 */
	const FIELD_NAMESPACE = 'namespace';

	/**
	 * @var string
	 */
	const FIELD_INTERWIKI = 'interwiki';

	/**
	 * @var string
	 */
	const FIELD_USAGE_COUNT = 'usage count';

	/**
	 * @var string
	 */
	const TYPE_CHAR_NOCASE = 'char nocase';

	/**
	 * @var string
	 */
	const TYPE_BOOL = 'boolean';

	/**
	 * @var string
	 */
	const TYPE_INT = 'integer';

	/**
	 * @var string
	 */
	const TYPE_INT_UNSIGNED = 'integer unsigned';

	/**
	 * @var string
	 */
	const TYPE_TEXT = 'text';

	/**
	 * @var string
	 */
	const TYPE_BLOB = 'blob';

	/**
	 * @var string
	 */
	const TYPE_DOUBLE = 'double';

	/**
	 * @since 2.5
	 *
	 * @param string|array $type
	 * @param array $fieldTypes
	 */
	public static function mapType( $type, $fieldTypes = array() ) {

		$fieldType = $type;
		$auxilary = '';

		// [ FieldType::FIELD_ID, 'NOT NULL' ]
		if ( is_array( $type ) && count( $type ) > 1 ) {
			$fieldType = $type[0];
			$auxilary = ' ' . $type[1];
		} elseif ( is_array( $type ) ) {
			$fieldType = $type[0];
		}

		if ( isset( $fieldTypes[$fieldType] ) ) {
			$fieldType = $fieldTypes[$fieldType];
		}

		return $fieldType . $auxilary;
	}

}
