<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SpecialPageList;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialPageList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialPageListTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SpecialPageList::class,
			new SpecialPageList()
		);
	}

	/**
	 * @dataProvider specialPageDataProvider
	 */
	public function testInitSpecialPageList( $name ) {

		$vars = [];

		$instance = new SpecialPageList();
		$instance->process( $vars );

		$this->assertArrayHasKey(
			$name,
			$vars
		);
	}

	public function specialPageDataProvider() {

		$specials = [
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'Concepts',
			'ExportRDF',
			'Types',
			'URIResolver',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
			'ProcessingErrorList',
			'PropertyLabelSimilarity'
		];

		foreach ( $specials as $special ) {
			yield [
				$special
			];
		}
	}

}
