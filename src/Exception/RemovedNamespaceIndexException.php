<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * Thrown when `$smwgNamespaceIndex` is set after SMW 7.0 removed it. Tells the
 * user how to migrate to MediaWiki core's per-constant override mechanism.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class RemovedNamespaceIndexException extends RuntimeException {

	/**
	 * @since 7.0.0
	 */
	public function __construct( int $oldValue ) {
		$snippets = sprintf(
			"define( 'SMW_NS_PROPERTY', %d );\n" .
			"define( 'SMW_NS_PROPERTY_TALK', %d );\n" .
			"define( 'SMW_NS_CONCEPT', %d );\n" .
			"define( 'SMW_NS_CONCEPT_TALK', %d );\n" .
			"define( 'SMW_NS_SCHEMA', %d );\n" .
			"define( 'SMW_NS_SCHEMA_TALK', %d );",
			$oldValue + 2,
			$oldValue + 3,
			$oldValue + 8,
			$oldValue + 9,
			$oldValue + 12,
			$oldValue + 13
		);

		parent::__construct(
			"\$smwgNamespaceIndex (was set to $oldValue) has been removed in SMW 7.0.\n\n" .
			"Remove the \$smwgNamespaceIndex line from LocalSettings.php. " .
			"To use non-default namespace IDs, define the constants directly in " .
			"LocalSettings.php BEFORE wfLoadExtension( 'SemanticMediaWiki' ):\n\n" .
			$snippets . "\n\n" .
			"See the SMW 7.0 release notes for details."
		);
	}
}
