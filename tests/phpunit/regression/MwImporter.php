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
	protected $exception = false;
	protected $verbose = false;

	public function __construct( $file = null, $verbose = false ) {
		$this->file = $file;
		$this->verbose = $verbose;

		if ( !is_readable( $this->file ) ) {
			throw new RuntimeException( "Source file is not accessible" );
		}

	}

	public function run() {

		$this->unregisterUploadsource();

		$source = ImportStreamSource::newFromFile( $this->file );

		if ( !$source->isGood() ) {
			throw new RuntimeException( "ImportStreamSource was not available" );
		}

		$importer = new WikiImporter( $source->value );
		$importer->setDebug( $this->verbose );

		$reporter = new ImportReporter(
			$importer,
			false,
			'',
			false
		);

		$reporter->setContext( new RequestContext() );
		$reporter->open();

		try {
			$importer->doImport();
		} catch ( MWException $e ) {
			$this->exception = $e;
		}

		return $reporter->close();
	}

	public function reportFailedImport() {

		if ( $this->exception ) {
			var_dump( 'exception: ', $this->exception->getMessage(), $this->exception->getTraceAsString() );
		}

		var_dump( 'result: ', $this->result->getWikiText() );
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
