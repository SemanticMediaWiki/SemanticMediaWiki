<?php

namespace SMW\Tests\MediaWiki;

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
class PageFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp() {
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
			->will( $this->returnValue( NS_MAIN ) );

		$instance = new PageFactory( $this->store );

		$this->setExpectedException( 'RuntimeException' );
		$instance->newPageFromTitle( $title );
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testNewPageFromTitle( $namespace, $expected ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $namespace ) );

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
