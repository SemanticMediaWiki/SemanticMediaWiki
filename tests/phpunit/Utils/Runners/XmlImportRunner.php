<?php

namespace SMW\Tests\Utils\Runners;

use ImportReporter;
use ImportStreamSource;
use MediaWiki\MediaWikiServices;
use RequestContext;
use RuntimeException;
use SMW\Tests\TestEnvironment;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
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
	protected float $importTime;

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
	 * @param bool $verbose
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
	 * @return bool
	 */
	public function run() {
		$this->unregisterUploadsource();
		$start = microtime( true );

		$source = ImportStreamSource::newFromFile(
			$this->assertThatFileIsReadableOrThrowException( $this->file )
		);

		if ( !$source->isGood() ) {
			throw new RuntimeException( 'Import returned with error(s) ' . serialize( $source->errors ) );
		}

		$services = MediaWikiServices::getInstance();

		$importer = $services->getWikiImporterFactory()->getWikiImporter(
			$source->value,
			RequestContext::getMain()->getAuthority()
		);
		$importer->setDebug( $this->verbose );

		if ( version_compare( MW_VERSION, '1.42', '>=' ) ) {
			$reporter = new ImportReporter(
				$importer,
				false,
				'',
				false,
				$this->acquireRequestContext()
			);
		} else {
			$reporter = new ImportReporter(
				$importer,
				false,
				'',
				false
			);
			$reporter->setContext( $this->acquireRequestContext() );
		}
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
	 * @return int
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
