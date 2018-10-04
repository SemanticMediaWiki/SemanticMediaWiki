<?php

namespace SMW\Tests\Utils\Validators;

use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class ValidatorFactory {

	/**
	 * @since 2.1
	 *
	 * @return StringValidator
	 */
	public function newStringValidator() {
		return new StringValidator();
	}

	/**
	 * @since 2.1
	 *
	 * @return NumberValidator
	 */
	public function newNumberValidator() {
		return new NumberValidator();
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticDataValidator
	 */
	public function newSemanticDataValidator() {
		return new SemanticDataValidator();
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 *
	 * @return IncomingSemanticDataValidator
	 */
	public function newIncomingSemanticDataValidator( Store $store ) {
		return new IncomingSemanticDataValidator( $store );
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryResultValidator
	 */
	public function newQueryResultValidator() {
		return new QueryResultValidator();
	}

	/**
	 * @since 2.1
	 *
	 * @return ExportDataValidator
	 */
	public function newExportDataValidator() {
		return new ExportDataValidator();
	}

	/**
	 * @since 2.1
	 *
	 * @return TitleValidator
	 */
	public function newTitleValidator() {
		return new TitleValidator();
	}

	/**
	 * @since 2.2
	 *
	 * @return QuerySegmentValidator
	 */
	public function newQuerySegmentValidator() {
		return new QuerySegmentValidator();
	}

	/**
	 * @since 3.0
	 *
	 * @return HtmlValidator
	 */
	public function newHtmlValidator() {
		return new HtmlValidator();
	}

}
