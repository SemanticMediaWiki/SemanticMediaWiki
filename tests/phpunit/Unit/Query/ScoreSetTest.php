<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\ScoreSet;

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

	public function testAsTable() {

		$instance = new ScoreSet();

		$instance->addScore( 'Foo', 42 );
		$instance->addScore( 'Bar', 1001, 5 );
		$instance->addScore( 'Foobar', 1 );

		$table = $instance->asTable();

		$this->assertContains(
			'<tr><td>42</td><td>Foo</td><td>0</td></tr>',
			$table
		);

		$this->assertContains(
			'<tr><td>1001</td><td>Bar</td><td>5</td></tr>',
			$table
		);
	}

}
