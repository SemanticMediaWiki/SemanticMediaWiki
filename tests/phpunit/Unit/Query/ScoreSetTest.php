<?php

namespace SMW\Tests\Query;

use SMW\Query\ScoreSet;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Query\ScoreSet
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ScoreSetTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ScoreSet::class,
			new ScoreSet()
		);
	}

	public function testAddScore() {

		$instance = new ScoreSet();

		$instance->addScore( 'Foo', 0.1 );

		$this->assertEquals(
			0.1,
			$instance->getScore( 'Foo' )
		);

		$this->assertFalse(
			$instance->getScore( 'Bar' )
		);
	}

	public function testAddScore_DIWikiPage() {

		$dataItem = DIWikiPage::newFromText( 'Bar' );
		$instance = new ScoreSet();

		$instance->addScore( $dataItem, 10 );

		$this->assertEquals(
		10,
			$instance->getScore( $dataItem )
		);
	}

	public function testGetScores() {

		$instance = new ScoreSet();

		$instance->addScore( 'Foo', 42 );
		$instance->addScore( 'Bar', 1001 );

		$this->assertEquals(
			[
				[ 'Foo', 42 ],
				[ 'Bar', 1001 ]
			],
			$instance->getScores()
		);
	}

}
