<?php

namespace SMW\Query\ResultPrinters;

use SMWQuery as Query;
use SMW\Query\Language\NamespaceDescription;

trait PrefixParameterProcessor {

	private bool $mixedResults;
	private string $prefix;

	public function setPrefix( string $prefix ) {
		$this->prefix = $prefix;
	}

	public function setQuery( Query $query ) {
		$this->mixedResults = $this->determineMixedResults( $query );
	}

	private function determineMixedResults( Query $query ) : bool {
		// this is a basic implementation, possibly to be expanded,
		// to guess whether result entries are expected to have
		// homogeneous or mixed prefixes
		return !( $query->getDescription() instanceof NamespaceDescription );
	}

	public function useLongText( bool $isSubject ) : bool {
		$prefix = $this->prefix;

		if ( $prefix === 'all'
			|| ( $prefix === 'subject' && $isSubject )
			|| ( $prefix === 'auto' && $isSubject && $this->mixedResults ) ) {
			return true;
		}

		return false;
	}

}
