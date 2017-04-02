<?php

namespace SMW\Tests\Utils;

use SMW\Tests\Utils\File\JsonFileReader;
use SMW\Tests\Utils\File\BulkFileProvider;
use SMW\Tests\Utils\File\LocalFileUpload;
use SMW\Tests\Utils\File\DummyFileCreator;
use SMW\Tests\Utils\Fixtures\FixturesFactory;
use SMW\Tests\Utils\Page\PageEditor;
use SMW\Tests\Utils\Runners\RunnerFactory;
use SMW\Tests\Utils\Validators\ValidatorFactory;
use Onoi\MessageReporter\SpyMessageReporter;

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
	 * @return MwApiFactory
	 */
	public function newMwApiFactory() {
		return new MwApiFactory();
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

	/**
	 * @since 2.2
	 *
	 * @return PageReader
	 */
	public function newPageReader() {
		return new PageReader();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $file|null
	 *
	 * @return JsonFileReader
	 */
	public function newJsonFileReader( $file = null ) {
		return new JsonFileReader( $file );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $path
	 *
	 * @return BulkFileProvider
	 */
	public function newBulkFileProvider( $path ) {
		return new BulkFileProvider( $path );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $localUploadPath
	 * @param $desiredDestName
	 *
	 * @return LocalFileUpload
	 */
	public function newLocalFileUploadWithCopy( $localUploadPath, $desiredDestName ) {

		// Use to create a copy to avoid having the orginal file being
		// deleted after the upload
		$dummyFileCreator = new DummyFileCreator();
		$dummyFileCreator->createFileWithCopyFrom(  $desiredDestName, $localUploadPath );

		return new LocalFileUpload(
			$dummyFileCreator->getPath(),
			$desiredDestName
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return LocalFileUpload
	 */
	public function newLocalFileUpload() {

		$localFileUpload = new LocalFileUpload();

		$localFileUpload->setDummyFileCreator(
			new DummyFileCreator()
		);

		return $localFileUpload;
	}

	/**
	 * @since 2.5
	 *
	 * @param $length
	 * @param $prefix identify a specific random string during testing
	 *
	 * @return string
	 */
	public function createRandomString( $length = 10, $prefix = null ) {
		return $prefix . ( $prefix ? '-' : '' ) . substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * @since 2.5
	 *
	 * @return SpyLogger
	 */
	public function newSpyLogger() {
		return new SpyLogger();
	}

	/**
	 * @since 2.5
	 *
	 * @return SpyMessageReporter
	 */
	public function newSpyMessageReporter() {
		return new SpyMessageReporter();
	}

}
