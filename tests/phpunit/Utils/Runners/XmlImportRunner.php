<?php

namespace SMW\Tests\Utils\Runners;

use ImportReporter;
use ImportStreamSource;
use RequestContext;
use RuntimeException;
use SMW\Tests\TestEnvironment;
use WikiImporter;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class XmlImportRunner {

	protected $file = null;
	protected $requestContext = null;
	protected $exception = false;
	protected $result = null;
	protected $verbose = false;

	/**
	 * @var TestEnvironment
	 */
	private $testEnvironment;

	public function __construct( $file = null ) {
		$this->file = $file;
		$this->testEnvironment = new TestEnvironment();
	}

	/**
	 * @param string $file
	 */
	public function setFile( $file ) {
		$this->file = $file;
	}

	/**
	 * @param boolean $verbose
	 *
	 * @return XmlImportRunner
	 */
	public function setVerbose( $verbose = true ) {
		$this->verbose = $verbose;
		return $this;
	}

	/**
	 * @param RequestContext $requestContext
	 */
	public function setRequestContext( RequestContext $requestContext ) {
		$this->requestContext = $requestContext;
	}

	/**
	 * @throws RuntimeException
	 * @return boolean
	 */
	public function run() {

		$this->unregisterUploadsource();
		$start = microtime( true );
		$config = null;

		$source = ImportStreamSource::newFromFile(
			$this->assertThatFileIsReadableOrThrowException( $this->file )
		);

		if ( !$source->isGood() ) {
			throw new RuntimeException( 'Import returned with error(s) ' . serialize( $source->errors ) );
		}

		// WikiImporter::__construct without a Config instance was deprecated in MediaWiki 1.25.
		if ( class_exists( '\ConfigFactory' ) ) {
			$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'main' );
		}

		$importer = new WikiImporter( $source->value, $config );
		$importer->setDebug( $this->verbose );

		$reporter = new ImportReporter(
			$importer,
			false,
			'',
			false
		);

		$reporter->setContext( $this->acquireRequestContext() );
		$reporter->open();
		$this->exception = false;

		try {
			$importer->doImport();
		} catch ( \Exception $e ) {
			$this->exception = $e;
		}

		$this->result = $reporter->close();
		$this->importTime = microtime( true ) - $start;

		$this->testEnvironment->executePendingDeferredUpdates();

		return $this->result->isGood() && !$this->exception;
	}

	/**
	 * @throws RuntimeException
	 */
	public function reportFailedImport() {

		$exceptionAsString = '';

		if ( $this->exception ) {
			$exceptionAsString = $this->exception->getMessage() . '#' . $this->exception->getTraceAsString();
		}

		throw new RuntimeException(
			'Import failed with ' . "\n" .
			$exceptionAsString . "\n" .
			$this->result->getWikiText()
		);
	}

	/**
	 * @return integer
	 */
	public function getElapsedImportTimeInSeconds() {
		return round( $this->importTime, 7 );
	}

	protected function acquireRequestContext() {

		if ( $this->requestContext === null ) {
			$this->requestContext = new RequestContext();
		}

		return $this->requestContext;
	}

	private function assertThatFileIsReadableOrThrowException( $file ) {

		$file = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $file );

		if ( is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "Source file {$file} is not accessible" );
	}

	/**
	 * MW has an implementation issue on how to use stream_wrapper_register
	 * which causes a "Protocol uploadsource:// is already defined" failure when
	 * used multiple times during import
	 *
	 * @see  https://gerrit.wikimedia.org/r/#/c/94351/
	 */
	private function unregisterUploadsource() {
		if ( in_array( 'uploadsource', stream_get_wrappers() ) ) {
			stream_wrapper_unregister( 'uploadsource' );
		}
	}

}
