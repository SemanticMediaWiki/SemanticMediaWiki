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

	private $specialPageFactory;

	protected function setUp() : void {
		parent::setUp();

		$this->specialPageFactory = $this->getMockBuilder( '\SMW\MediaWiki\SpecialPageFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SpecialPageList::class,
			new SpecialPageList( $this->specialPageFactory )
		);
	}

	/**
	 * @dataProvider specialPageDataProvider
	 */
	public function testInitSpecialPageList( $name ) {

		$vars = [];

		$instance = new SpecialPageList(
			$this->specialPageFactory
		);

		$instance->setOptions(
			[
				'SMW_EXTENSION_LOADED' => true
			]
		);

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
			'PropertyLabelSimilarity',
			'PendingTaskList'
		];

		foreach ( $specials as $special ) {
			yield [
				$special
			];
		}
	}

}
