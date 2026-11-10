<?php

namespace SMW\Utils;

/**
 * Bounds the total number of concept expansions performed while building one
 * query. The Elastic and SPARQL query engines re-interpret a referenced concept
 * once per path, so an acyclic concept fan-out (a DAG whose shared sub-concepts
 * are reached by several disjuncts) would otherwise grow the query build without
 * limit. This is a per-request safety bound, kept separate from the semantic
 * query-size limits ($smwgQMaxSize / $smwgQMaxDepth).
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class ConceptExpansionGuard {

	public const DEFAULT_MAX_EXPANSIONS = 1000;

	private int $expansions = 0;
	private int $active = 0;

	public function __construct( private readonly int $maxExpansions ) {
	}

	/**
	 * Register the start of one concept expansion for the current query build.
	 * Returns false once the budget is exhausted, in which case the caller must
	 * not expand the concept. The budget resets when the previous build has fully
	 * unwound (no expansion active), so each top-level query starts fresh. Pair
	 * every true return with a release().
	 */
	public function acquire(): bool {
		if ( $this->active === 0 ) {
			$this->expansions = 0;
		}

		if ( $this->expansions >= $this->maxExpansions ) {
			return false;
		}

		$this->expansions++;
		$this->active++;

		return true;
	}

	public function release(): void {
		if ( $this->active > 0 ) {
			$this->active--;
		}
	}

}
