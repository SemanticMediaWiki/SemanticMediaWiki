<?php

namespace SMW\Query\ResultPrinters;

use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Localizer\Localizer;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\PrintRequest;
use SMW\DataValues\WikiPageValue;
use SMW\Query\Query;
use SMWDataValue as DataValue;

class PrefixParameterProcessor {

	private bool $mixedResults = false;

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

		private function isNamespaceBearingValue( ?DataValue $dataValue ): bool {
		if ( $dataValue === null ) {
			return false;
		}

		// Fall back to the property key when a property is attached.
		// `_INST` / `_CONC` are the property-keyed forms whose canonical
		// printed output includes the namespace; the special category
		// membership check (`?Category:Foo`, PRINT_CCAT) is handled by
		// the mode check in useLongText() and never reaches this branch.
		$property = $dataValue->getProperty();

		if ( $property === null ) {
			return false;
		}

		return in_array( $property->getKey(), [
			DIProperty::TYPE_CATEGORY,   // '_INST'
			DIProperty::TYPE_CONCEPT,    // '_CONC'
		], true );
	}

	public function useLongText( ?PrintRequest $printRequest, ?DataValue $dataValue = null ): bool {
		$printRequestMode = $printRequest?->getMode() ?? PrintRequest::PRINT_PROP;
		$isSubject = $printRequestMode === PrintRequest::PRINT_THIS;

error_log( sprintf(
	'useLongText: prefix=%s mode=%s isWPV=%s hasPipetrick=%s dvFeatures=%s',
	var_export( $this->prefix, true ),
	var_export( $printRequestMode, true ),
	var_export( $dataValue instanceof WikiPageValue, true ),
	var_export(
		$dataValue instanceof WikiPageValue ? $dataValue->hasFeature( SMW_DV_WPV_PIPETRICK ) : null,
		true
	),
	var_export( $GLOBALS['smwgDVFeatures'] ?? null, true )
) );
		
		if ( $this->prefix === 'all' ) {
			return true;
		}

		if ( $this->prefix === 'none' ) {
			return false;
		}

		// `?Category:Foo` (PRINT_CCAT) is a boolean membership check and
		// must keep the short form.
		if ( $printRequestMode === PrintRequest::PRINT_CCAT ) {
			return false;
		}

		// `?Category` (PRINT_CATS) prints the canonical `Category:Foo` form.
		// The lowercase `?category` alias is canonicalized to `?Category`
		// by the deserializer, so it cannot be distinguished here; both
		// render canonically.
		if ( $printRequestMode === PrintRequest::PRINT_CATS ) {
			return true;
		}

		// Pipe-trick-eligible values rely on the short caption
		// (getShortCaptionText() applies Pipetrick::apply()). Forcing long
		// text would suppress the pipe trick, so keep the short form for
		// non-subject property printouts.
		if (
			$this->prefix === 'auto' &&
			( $printRequestMode === PrintRequest::PRINT_PROP ||
			  $printRequestMode === PrintRequest::PRINT_THIS ) &&
			$dataValue instanceof WikiPageValue
		) {
			return false;
		}

		if ( $this->isNamespaceBearingValue( $dataValue ) ) {
			return true;
		}

		if ( $this->prefix === 'subject' && $isSubject ) {
			return true;
		}

		return $this->mixedResults && $isSubject;
	}
}
