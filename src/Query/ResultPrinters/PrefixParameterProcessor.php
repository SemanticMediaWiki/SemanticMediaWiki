<?php

namespace SMW\Query\ResultPrinters;

use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Localizer\Localizer;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\PrintRequest;
use SMW\MediaWiki\Pipetrick;
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
	/**
	 * Whether the pipe trick would change this title's display form.
	 *
	 * `Pipetrick::apply()` is the same transformation
	 * `WikiPageValue::getShortCaptionText()` uses when the
	 * `SMW_DV_WPV_PIPETRICK` feature is enabled. Comparing its result
	 * against the raw title tells us whether forcing the short form
	 * would actually produce a different (pipe-tricked) caption, which
	 * is the only case where `auto` should prefer short over long.
	 */
	private function isPipeTrickEligible( WikiPageValue $dataValue ): bool {
		$text = $dataValue->getText();

		return Pipetrick::apply( $text ) !== $text;
	}

	public function useLongText( ?PrintRequest $printRequest, ?DataValue $dataValue = null ): bool {
		$printRequestMode = $printRequest?->getMode() ?? PrintRequest::PRINT_PROP;
		$isSubject = $printRequestMode === PrintRequest::PRINT_THIS;

		if ( $this->prefix === 'all' ) {
			return true;
		}

		if ( $this->prefix === 'none' ) {
			return false;
		}

		// `?Category` (PRINT_CATS) always prints the canonical
		// `Category:Foo` form, independent of the prefix mode.
		if ( $printRequestMode === PrintRequest::PRINT_CATS ) {
			return true;
		}

		// `?Category:Foo` (PRINT_CCAT) is a boolean membership check.
		if ( $printRequestMode === PrintRequest::PRINT_CCAT ) {
			return false;
		}

		// Under `auto`, a page value whose title has a pipe-trick
		// disambiguator renders short so the pipe trick applies.
		// Ordinary prefixed titles keep the long form.
		if (
			$this->prefix === 'auto' &&
			( $printRequestMode === PrintRequest::PRINT_PROP ||
			  $printRequestMode === PrintRequest::PRINT_THIS ) &&
			$dataValue instanceof WikiPageValue &&
			$this->isPipeTrickEligible( $dataValue )
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
