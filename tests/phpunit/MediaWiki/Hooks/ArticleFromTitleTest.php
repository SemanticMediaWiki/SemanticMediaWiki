<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ArticleFromTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleFromTitle
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleFromTitleTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleFromTitle::class,
			new ArticleFromTitle( $this->store )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testProcess( $namespace, $expected ) {
		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ArticleFromTitle( $this->store );
		$instance->process( $title, $wikiPage );

		$this->assertInstanceOf(
			$expected,
			$wikiPage
		);
	}

	public function namespaceProvider() {
		$provider[] = [
			SMW_NS_PROPERTY,
			'SMW\MediaWiki\Page\PropertyPage'
		];

		$provider[] = [
			SMW_NS_CONCEPT,
			'SMW\MediaWiki\Page\ConceptPage'
		];

		return $provider;
	}

}
