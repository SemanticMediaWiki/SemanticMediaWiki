<?php

namespace SMW\Test;

use ImportStreamSource;
use ImportReporter;
use WikiImporter;
use MWException;
use RequestContext;

use RuntimeException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class MwImporter {

	protected $file = null;
	protected $requestContext = null;
	protected $exception = false;
	protected $result = null;
	protected $verbose = false;

	public function __construct( $file = null ) {
		$this->file = $file;

		if ( !is_readable( $this->file ) ) {
			throw new RuntimeException( "Source file {$this->file} is not accessible" );
		}

	}

	/**
	 * @param boolean $verbose
	 */
	public function setVerbose( $verbose = true ) {
		$this->verbose = $verbose;
	}

	/**
	 * @param RequestContext $requestContext
	 */
	public function setRequestContext( RequestContext $requestContext ) {
		$this->requestContext = $requestContext;
	}

	/**
	 * @throws RuntimeException
	 * @return Status
	 */
	public function run() {

		$this->unregisterUploadsource();
		$start = microtime( true );

		$source = ImportStreamSource::newFromFile( $this->file );

		if ( !$source->isGood() ) {
			throw new RuntimeException( 'Import contained errors ' . serialize( $source->errors ) );
		}

		$importer = new WikiImporter( $source->value );
		$importer->setDebug( $this->verbose );

		$reporter = new ImportReporter(
			$importer,
			false,
			'',
			false
		);

		$reporter->setContext( $this->acquireRequestContext() );
		$reporter->open();

		try {
			$importer->doImport();
		} catch ( MWException $e ) {
			$this->exception = $e;
		}

		$this->result = $reporter->close();
		$this->importTime = microtime( true ) - $start;

		return $this->result;
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
			'Import failed with ' . '#' .
			$exceptionAsString . '#' .
			$this->result->getWikiText()
		);
	}

	/**
	 * @return integer
	 */
	public function getImportTimeAsSeconds() {
		return round( $this->importTime , 7 );
	}

	protected function acquireRequestContext() {

		if ( $this->requestContext === null ) {
			$this->requestContext = new RequestContext();
		}

		return $this->requestContext;
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
