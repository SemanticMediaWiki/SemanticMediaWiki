<?php

namespace SMW\Maintenance;

use SMW\Options;

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
	 * @var string
	 */
	private $exceptionFile;

	/**
	 * @var integer
	 */
	private $exceptionCounter = 0;

	/**
	 * @since 2.4
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace = 'smw' ) {
		$this->namespace = $namespace;
	}

	/**
	 * @since 2.4
	 *
	 * @param Options $options
	 */
	public function setOptions( Options $options ) {

		$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		$this->exceptionFile = $options->has( 'exception-log' ) ? $options->get( 'exception-log' ) : __DIR__ . "../../../";

		//if ( !is_writable( $this->exceptionFile ) ) {
		//	die( "`$this->exceptionFile` is not writable.\n" );
		//}

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
	public function getExceptionCounter() {
		return $this->exceptionCounter;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $exceptionLogMessages
	 */
	public function doWriteExceptionLog( array $exceptionLogMessages ) {
		foreach ( $exceptionLogMessages as $id => $exception ) {

			if ( !is_array( $exception ) ) {
				continue;
			}

			$this->exceptionCounter++;
			$this->writeLogFile( $id, $exception );
		}
	}

	private function writeLogFile( $id, $exception ) {

		$text = "\n======== EXCEPTION ======\n" .
			"$id | " . $exception['msg'] . "\n\n" .
			$exception['trace'] . "\n" .
			"======== END ======" ."\n";

		file_put_contents(
			$this->exceptionFile,
			$text,
			FILE_APPEND
		);
	}

}
