<?php

namespace SMW\Tests\Reporter;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class MessageReporterTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * @return MessageReporter[]
	 */
	public abstract function getInstances();

	/**
	 * Message provider, includes edge cases and random tests
	 *
	 * @return array
	 */
	public function reportMessageProvider() {
		$messages = array();

		$messages[] = '';
		$messages[] = '  ';

		foreach ( array_merge( range( 1, 100 ), array( 1000, 10000 ) ) as $length ) {
			$string = array();

			for ( $position = 0; $position < $length; $position++ ) {
				$string[] = chr( mt_rand( 32, 126 ) );
			}

			$messages[] = implode( '', $string );
		}

		return $this->arrayWrap( $messages );
	}

	/**
	 * @dataProvider reportMessageProvider
	 *
	 * @param string $message
	 */
	public function testReportMessage( $message ) {
		foreach ( $this->getInstances() as $reporter ) {
			$reporter->reportMessage( $message );
			$reporter->reportMessage( $message );
			$this->assertTrue( true );
		}
	}

	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

}