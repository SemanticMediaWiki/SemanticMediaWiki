<?php

namespace SMW\Test;

use SMW\FeedResultPrinter;

use ReflectionClass;

/**
 * Tests for the FeedResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\FeedResultPrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class FeedResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FeedResultPrinter';
	}

	/**
	 * Helper method that returns a FeedResultPrinter object
	 *
	 * @return FeedResultPrinter
	 */
	private function newInstance( $parameters = array() ) {
		return $this->setParameters( new FeedResultPrinter( 'feed' ), $parameters );
	}

	/**
	 * @test FeedResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test FeedResultPrinter::feedItemDescription
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testFeedItemDescription( $setup, $expected, $message ) {

		$instance = $this->newInstance();

		$reflector = new ReflectionClass( '\SMW\FeedResultPrinter' );
		$method = $reflector->getMethod( 'feedItemDescription' );
		$method->setAccessible( true );

		$this->assertEquals(
			$expected['text'],
			$method->invoke( $instance, $setup['items'], $setup['pageContent'] ),
			'Failed asserting ' . $message['info']
		);

	}


	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0
		// http://www.utexas.edu/learn/html/spchar.html
		$provider[] = array(
			array(
				'items'       => array(),
				'pageContent' => 'Semantic MediaWiki Conference, have been announced: it will be held at' .
					'[http://www.aohostels.com/en/tagungen/tagungen-berlin/ A&O Berlin Hauptbahnhof]' .
					'&¢©«»—¡¿,åÃãÆç'
			),
			array(
				'text'        => 'Semantic MediaWiki Conference, have been announced: it will be held at' .
					'[http://www.aohostels.com/en/tagungen/tagungen-berlin/ A&O Berlin Hauptbahnhof]' .
					'&¢©«»—¡¿,åÃãÆç'
			),
			array( 'info'     => 'text enconding including html special characters' )
		);

		return $provider;

	}


}
