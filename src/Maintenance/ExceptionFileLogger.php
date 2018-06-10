<?php

namespace SMW\Maintenance;

use Exception;
use SMW\Options;
use SMW\Utils\File;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExceptionFileLogger {

	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var File
	 */
	private $file;

	/**
	 * @var string
	 */
	private $exceptionFile;

	/**
	 * @var integer
	 */
	private $exceptionCount = 0;

	/**
	 * @var array
	 */
	private $exceptionLogMessages = [];

	/**
	 * @since 2.4
	 *
	 * @param string $namespace
	 * @param File|null $file
	 */
	public function __construct( $namespace = 'smw', File $file = null ) {
		$this->namespace = $namespace;
		$this->file = $file;

		if ( $this->file === null ) {
			$this->file = new File();
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param Options $options
	 */
	public function setOptions( Options $options ) {

		$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		$this->exceptionFile = __DIR__ . "../../../";

		if ( $options->has( 'exception-log' ) ) {
			$this->exceptionFile = $options->get( 'exception-log' );
		}

		$this->exceptionFile .= $this->namespace . "-exceptions-" . $dateTimeUtc->format( 'Y-m-d' ) . ".log";
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getExceptionFile() {
		return realpath( $this->exceptionFile );
	}

	/**
	 * @since 2.4
	 *
	 * @return integer
	 */
	public function getExceptionCount() {
		return $this->exceptionCount;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 * @param Exception $exception
	 */
	public function recordException( $id, Exception $exception ) {
		$this->exceptionCount++;

		$this->exceptionLogMessages[$id] = [
			'msg' => $exception->getMessage(),
			'trace' => $exception->getTraceAsString()
		];
	}

	/**
	 * @since 3.0
	 */
	public function doWrite() {

		foreach ( $this->exceptionLogMessages as $id => $exception ) {
			$this->put( $id, $exception );
		}

		$this->exceptionLogMessages = [];
		$this->exceptionCount = 0;
	}

	private function put( $id, $exception ) {

		$text = "\n======== EXCEPTION ======\n" .
			"$id | " . $exception['msg'] . "\n\n" .
			$exception['trace'] . "\n" .
			"======== END ======" ."\n";

		$this->file->write( $this->exceptionFile, $text, FILE_APPEND );
	}

}
