<?php

namespace SMW\Tests\Query\ResultPrinters;

use ReflectionClass;
use SMW\Query\ResultPrinters\FeedExportPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\FeedExportPrinter
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class FeedExportPrinterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FeedExportPrinter::class,
			new FeedExportPrinter( 'feed' )
		);
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testFeedItemDescription( $setup, $expected, $message ) {

		$instance = new FeedExportPrinter( 'feed' );

		$reflector = new ReflectionClass( '\SMW\Query\ResultPrinters\FeedExportPrinter' );
		$method = $reflector->getMethod( 'feedItemDescription' );
		$method->setAccessible( true );

		$this->assertEquals(
			$expected['text'],
			$method->invoke( $instance, $setup['items'], $setup['pageContent'] )
		);
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = [];

		// #0
		// https://web.archive.org/web/20160802052417/http://www.utexas.edu/learn/html/spchar.html
		$provider[] = [
			[
				'items'       => [],
				'pageContent' => 'Semantic MediaWiki Conference, have been announced: it will be held at' .
					'[http://www.aohostels.com/en/tagungen/tagungen-berlin/ A&O Berlin Hauptbahnhof]' .
					'&¢©«»—¡¿,åÃãÆç'
			],
			[
				'text'        => 'Semantic MediaWiki Conference, have been announced: it will be held at' .
					'[http://www.aohostels.com/en/tagungen/tagungen-berlin/ A&O Berlin Hauptbahnhof]' .
					'&¢©«»—¡¿,åÃãÆç'
			],
			[ 'info'     => 'text encoding including html special characters' ]
		];

		return $provider;

	}


}
