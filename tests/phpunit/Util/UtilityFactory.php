<?php

namespace SMW\Tests\Util;

use SMW\Tests\Util\Validators\ValidatorFactory;
use SMW\Tests\Util\Fixtures\FixturesFactory;
use SMW\Tests\Util\Runners\RunnerFactory;
use SMW\Tests\Util\Page\PageEditor;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UtilityFactory {

	/**
	 * @var UtilityFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.1
	 *
	 * @return UtilityFactory
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
	 * @return ValidatorFactory
	 */
	public function newValidatorFactory() {
		return new ValidatorFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return StringBuilder
	 */
	public function newStringBuilder() {
		return new StringBuilder();
	}

	/**
	 * @since 2.1
	 *
	 * @return MwHooksHandler
	 */
	public function newMwHooksHandler() {
		return new MwHooksHandler();
	}

	/**
	 * @since 2.1
	 *
	 * @return ParserFactory
	 */
	public function newParserFactory() {
		return new ParserFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return FixturesFactory
	 */
	public function newFixturesFactory() {
		return new FixturesFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticDataFactory
	 */
	public function newSemanticDataFactory() {
		return new SemanticDataFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return RunnerFactory
	 */
	public function newRunnerFactory() {
		return new RunnerFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return PageDeleter
	 */
	public function newPageDeleter() {
		return new PageDeleter();
	}

	/**
	 * @since 2.1
	 *
	 * @return PageRefresher
	 */
	public function newPageRefresher() {
		return new PageRefresher();
	}

	/**
	 * @since 2.1
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return new PageCreator();
	}

	/**
	 * @since 2.1
	 *
	 * @return PageEditor
	 */
	public function newPageEditor() {
		return new PageEditor();
	}

}
