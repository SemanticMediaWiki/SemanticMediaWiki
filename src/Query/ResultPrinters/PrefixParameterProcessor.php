<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\Language\NamespaceDescription;
use SMWQuery as Query;

class PrefixParameterProcessor {

	private bool $mixedResults;

	public function __construct(
		private readonly Query $query,
		private readonly string $prefix,
	) {
		if ( $this->prefix === 'auto' ) {
			$this->mixedResults = $this->getMixedResults();
		}
	}

	private function getMixedResults(): bool {
		// this is a basic implementation, possibly to be expanded,
		// to guess whether result entries are expected to have
		// homogeneous or mixed prefixes
		return !( $this->query->getDescription() instanceof NamespaceDescription );
	}

	public function useLongText( bool $isSubject ): bool {
		$prefix = $this->prefix;

		if ( $prefix === 'all'
			|| ( $prefix === 'subject' && $isSubject )
			|| ( $prefix === 'auto' && $isSubject && $this->mixedResults ) ) {
			return true;
		}

		return false;
	}

}
