<?php

namespace SMW\Tests\MediaWiki;

use RuntimeException;
use SMW\MediaWiki\PageFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\PageFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PageFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageFactory::class,
			new PageFactory( $this->store )
		);
	}

	public function testNewPageFromNotRegisteredNamespaceThrowsException() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$instance = new PageFactory( $this->store );

		$this->expectException( 'RuntimeException' );
		$instance->newPageFromTitle( $title );
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testNewPageFromTitle( $namespace, $expected ) {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$instance = new PageFactory( $this->store );

		$this->assertInstanceOf(
			$expected,
			$instance->newPageFromTitle( $title )
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
