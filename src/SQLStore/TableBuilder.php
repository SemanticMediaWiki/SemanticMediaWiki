<?php

namespace SMW\SQLStore;

use SMW\SQLStore\TableBuilder\Table;
use Onoi\MessageReporter\MessageReporterAware;

/**
 * @private
 *
 * Provides generic creation and updating function for database tables. A builder
 * that implements this interface is expected to define Database specific
 * operations and allowing it to be executed on a specific RDBMS back-end.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface TableBuilder extends MessageReporterAware {

	/**
	 * Common prefix used by all Semantic MediaWiki tables
	 */
	const TABLE_PREFIX = 'smw_';

	/**
	 * On before the creation of tables and indicies
	 */
	const PRE_CREATION = 'pre.creation';

	/**
	 * On after creation of all tables
	 */
	const POST_CREATION = 'post.creation';

	/**
	 * On after dropping all tables
	 */
	const POST_DESTRUCTION = 'post.destruction';

	/**
	 * Generic creation and updating function for database tables. Ideally, it
	 * would be able to modify a table's signature in arbitrary ways, but it will
	 * fail for some changes. Its string-based interface is somewhat too
	 * impoverished for a permanent solution. It would be possible to go for update
	 * scripts (specific to each change) in the style of MediaWiki instead.
	 *
	 * Make sure the table of the given name has the given fields, provided
	 * as an array with entries fieldname => typeparams. typeparams should be
	 * in a normalised form and order to match to existing values.
	 *
	 * The function returns an array that includes all columns that have been
	 * changed. For each such column, the array contains an entry
	 * columnname => action, where action is one of 'up', 'new', or 'del'
	 *
	 * @note The function partly ignores the order in which fields are set up.
	 * Only if the type of some field changes will its order be adjusted explicitly.
	 *
	 * @since 2.5
	 *
	 * @param Table $table
	 */
	public function create( Table $table );

	/**
	 * Removes a table from the RDBMS backend.
	 *
	 * @since 2.5
	 *
	 * @param Table $table
	 */
	public function drop( Table $table );

	/**
	 * Database backends often have different types that need to be used
	 * repeatedly in (Semantic) MediaWiki. This function provides the
	 * preferred type (as a string) for various common kinds of columns.
	 * The input is one of the following strings: 'id' (page id numbers or
	 * similar), 'title' (title strings or similar), 'namespace' (namespace
	 * numbers), 'blob' (longer text blobs), 'iw' (interwiki prefixes).
	 *
	 * @since 2.5
	 *
	 * @param string|FieldType $fieldType
	 *
	 * @return string|false SQL type declaration
	 */
	public function getStandardFieldType( $fieldType );

	/**
	 * Allows to check and validate the build on specific events
	 *
	 * @since 2.5
	 *
	 * @param string $event
	 */
	public function checkOn( $event );

}
