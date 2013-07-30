<?php

namespace SMW\Test;
use SMW\ObservableMessageReporter;
use SMW\MessageReporter;

/**
 * Tests for the SMW\ObservableMessageReporter class.
 *
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 * @covers \SMW\ObservableMessageReporter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ObservableMessageReporterTest extends MessageReporterTest {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ObservableMessageReporter';
	}

	/**
	 * @return MessageReporter[]
	 */
	public function getInstances() {
		$instances = array();

		$instances[] = new ObservableMessageReporter();

		$reporter = new ObservableMessageReporter();
		$reporter->registerMessageReporter( new ObservableMessageReporter() );
		$callback0 = function( $string ) {};
		$callback1 = function( $string ) {};
		$instances[] = $reporter;

		$reporter = clone $reporter;
		$reporter->registerReporterCallback( $callback0 );
		$reporter->registerReporterCallback( $callback1 );
		$instances[] = $reporter;

		return $instances;
	}

	/**
	 * @dataProvider reportMessageProvider
	 *
	 * @param string $message
	 */
	public function testCallbackInvocation( $message ) {
		$callCount = 0;
		$asserter = array( $this, 'assertEquals' );

		$callback0 = function( $actual ) use ( $message, &$callCount, $asserter ) {
			$callCount += 1;
			call_user_func( $asserter, $message, $actual );
		};

		$callback1 = function( $actual ) use ( $message, &$callCount, $asserter ) {
			$callCount += 1;
			call_user_func( $asserter, $message, $actual );
		};

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( $callback0 );
		$reporter->registerReporterCallback( $callback1 );

		$reporter->reportMessage( $message );

		$this->assertEquals( 2, $callCount );

		$reporter->reportMessage( $message );

		$this->assertEquals( 4, $callCount );
	}

	/**
	 * @dataProvider reportMessageProvider
	 *
	 * @param string $message
	 */
	public function testReporterInvocation( $message ) {
		$callCount = 0;
		$asserter = array( $this, 'assertEquals' );

		$callback0 = function( $actual ) use ( $message, &$callCount, $asserter ) {
			$callCount += 1;
			call_user_func( $asserter, $message, $actual );
		};

		$callback1 = function( $actual ) use ( $message, &$callCount, $asserter ) {
			$callCount += 1;
			call_user_func( $asserter, $message, $actual );
		};

		$reporter0 = new ObservableMessageReporter();
		$reporter0->registerReporterCallback( $callback0 );

		$reporter1 = new ObservableMessageReporter();
		$reporter1->registerReporterCallback( $callback1 );

		$reporter = new ObservableMessageReporter();
		$reporter->registerMessageReporter( $reporter0 );
		$reporter->registerMessageReporter( $reporter1 );

		$reporter->reportMessage( $message );

		$this->assertEquals( 2, $callCount );

		$reporter->reportMessage( $message );

		$this->assertEquals( 4, $callCount );
	}

}

/**
 * Tests for the SMW\MessageReporter class.
 *
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQueries
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class MessageReporterTest extends SemanticMediaWikiTestCase {

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

}