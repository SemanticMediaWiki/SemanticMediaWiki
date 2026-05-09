<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Query;

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
		// prefix can be 'all', 'subject', 'none', 'auto' 
		if ( $this->prefix === 'all') {
			return true;
		}

		if ( $this->prefix === 'none') {
			return false;
		}

		// add prefix to subject
		if ( $this->prefix === 'subject' && $isSubject ) {
			return true;
		}

		// prefix is auto
		return $this->mixedResults;
	}

}
