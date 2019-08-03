<?php

namespace SMW\Tests\Utils\JSONScript;

use SMW\Tests\Utils\File\ContentsReader;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ApiTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MwApiFactory
	 */
	private $mwApiFactory;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @param MwApiFactory mwApiFactory
	 * @param StringValidator
	 */
	public function __construct( $mwApiFactory, $stringValidator ) {
		$this->mwApiFactory = $mwApiFactory;
		$this->stringValidator = $stringValidator;
	}

	/**
	 * @since 3.0
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $testCaseLocation
	 */
	public function setTestCaseLocation( $testCaseLocation ) {
		$this->testCaseLocation = $testCaseLocation;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $case
	 */
	public function process( array $case ) {

		if ( !isset( $case['api'] ) ) {
			return;
		}

		$parameters = [];

		if ( isset( $case['api']['parameters'] ) ) {
			$parameters = $case['api']['parameters'];
		}

		$res = $this->mwApiFactory->doApiRequest(
			$parameters
		);

		$this->assertOutputForCase( $case, json_encode( $res ) );
	}

	private function assertOutputForCase( $case, $text ) {

		// Avoid issue with \r carriage return and \n new line
		$text = str_replace( "\r\n", "\n", $text );

		if ( isset( $case['assert-output']['to-contain'] ) ) {

			if ( isset( $case['assert-output']['to-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['to-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['to-contain'];
			}

			$this->stringValidator->assertThatStringContains(
				$contents,
				$text,
				$case['about']
			);
		}

		if ( isset( $case['assert-output']['not-contain'] ) ) {

			if ( isset( $case['assert-output']['not-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['not-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['not-contain'];
			}

			$this->stringValidator->assertThatStringNotContains(
				$contents,
				$text,
				$case['about']
			);
		}
	}

}
