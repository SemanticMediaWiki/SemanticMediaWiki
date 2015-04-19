<?php

namespace SMW\Tests\Utils\Validators;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class ValidatorFactory {

	/**
	 * @var ValidatorFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.1
	 *
	 * @return ValidatorFactory
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

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
	 * @return SemanticDataValidator
	 */
	public function newSemanticDataValidator() {
		return new SemanticDataValidator();
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

}
