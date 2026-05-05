<?php

namespace SMW\MediaWiki\Connection;

/**
 * SQL-fragment builder for callbacks passed via
 * `RequestOptions::addExtraCondition()`. Produces individually quoted WHERE
 * fragments (`eq`, `neq`, `in`, `like`) that the receiver concatenates into
 * a raw SQL clause.
 *
 * Replaces the formatter-as-fragment-builder shape of the old
 * `SMW\MediaWiki\Connection\Query` class for this specific use case. The
 * fuller `Query` builder (type/table/fields/options/build/execute) was
 * deleted as part of the same change — the planner-level SQL formatting
 * lives in MW core's QueryBuilder layer now.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class SqlFragmentBuilder {

	/**
	 * Public for parity with the previous `Query` formatter. Third-party
	 * callbacks may read these to build cross-alias join expressions where
	 * the components are needed separately. The third callback argument
	 * is the concatenation `$alias . $index` (e.g. `"t1"`); these
	 * properties expose the components as `"t"` and `1` respectively.
	 */
	public string $alias = '';

	public int $index = 0;

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function eq( string $k, ?string $v ): string {
		return "$k=" . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 7.0.0
	 */
	public function neq( string $k, ?string $v ): string {
		return "$k!=" . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 7.0.0
	 */
	public function in( string $k, array $v ): string {
		return "$k IN (" . $this->connection->makeList( $v ) . ')';
	}

	/**
	 * @since 7.0.0
	 */
	public function like( string $k, string $v ): string {
		return "$k LIKE " . $this->connection->addQuotes( $v );
	}

}
