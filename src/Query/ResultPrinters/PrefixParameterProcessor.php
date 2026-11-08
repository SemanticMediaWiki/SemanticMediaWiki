<?php

namespace SMW\Query\ResultPrinters;

use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Query;
use SMWDataValue as DataValue;

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
		// A namespace-restricted query is assumed to yield homogeneous
		// prefixes; anything else is treated as potentially mixed.
		return !( $this->query->getDescription() instanceof NamespaceDescription );
	}

	/**
	 * Values whose long text carries semantic meaning beyond the bare
	 * page name (e.g. `Category:Foo`, `Property:Bar`, `Concept:Baz`)
	 * must always be shown with their namespace prefix, otherwise the
	 * rendered result is indistinguishable from an ordinary page of the
	 * same name and breaks tests such as format-table-0003.
	 */
	private function isNamespaceBearingValue( ?DataValue $dataValue ): bool {
		if ( $dataValue === null ) {
			return false;
		}

		// Category / Concept values are identified by their data item
		// namespace, which is independent of the property context.
		$dataItem = $dataValue->getDataItem();

		if ( $dataItem instanceof \SMW\DIWikiPage ) {
			$ns = $dataItem->getNamespace();

			if ( in_array( $ns, [ NS_CATEGORY, SMW_NS_CONCEPT, SMW_NS_PROPERTY ], true ) ) {
				return true;
			}
		}

		// Fall back to the property key when a property is attached.
		$property = $dataValue->getProperty();

		if ( $property === null ) {
			return false;
		}

		return in_array( $property->getKey(), [
			DIProperty::TYPE_CATEGORY,   // '_INST'
			DIProperty::TYPE_CONCEPT,    // '_CONC'
		], true );
	}

	public function useLongText( bool $isSubject, ?DataValue $dataValue = null ): bool {
		// Explicit modes take precedence.
		if ( $this->prefix === 'all' ) {
			return true;
		}

		if ( $this->prefix === 'none' ) {
			return false;
		}

		// Namespace-bearing values always use their long text, regardless
		// of the prefix mode. The prefix is part of the value's identity.
		if ( $this->isNamespaceBearingValue( $dataValue ) ) {
			return true;
		}

		// 'subject' prefixes the subject only.
		if ( $this->prefix === 'subject' && $isSubject ) {
			return true;
		}

		// 'auto': when results may have mixed prefixes, prefix the subject
		// so the reader can tell which namespace each row belongs to.
		// Non-subject values fall back to short text unless they are
		// namespace-bearing (handled above).
		return $this->mixedResults && $isSubject;
	}
}
