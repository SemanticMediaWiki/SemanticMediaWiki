<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\Excerpts;

/**
 * @covers \SMW\Query\Excerpts
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ExcerptsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Excerpts::class,
			new Excerpts()
		);
	}

	public function testAddExcerpt() {

		$instance = new Excerpts();

		$instance->addExcerpt( 'Foo', 0.1 );

		$this->assertEquals(
			0.1,
			$instance->getExcerpt( 'Foo' )
		);

		$this->assertFalse(
			$instance->getExcerpt( 'Bar' )
		);
	}

	public function testAddExcerpt_DIWikiPage() {

		$dataItem = DIWikiPage::newFromText( 'Bar' );
		$instance = new Excerpts();

		$instance->addExcerpt( $dataItem, 10 );

		$this->assertEquals(
			10,
			$instance->getExcerpt( $dataItem )
		);
	}

	public function testGetExcerpts() {

		$instance = new Excerpts();

		$instance->addExcerpt( 'Foo', '...' );
		$instance->addExcerpt( 'Bar', 1001 );

		$this->assertEquals(
			[
				[ 'Foo', '...' ],
				[ 'Bar', 1001 ]
			],
			$instance->getExcerpts()
		);
	}

}
