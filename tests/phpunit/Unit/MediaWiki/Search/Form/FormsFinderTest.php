<?php

namespace SMW\Tests\MediaWiki\Search\Form;

use SMW\DIWikiPage;
use SMW\MediaWiki\Search\Form\FormsFinder;

/**
 * @covers \SMW\MediaWiki\Search\Form\FormsFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FormsFinder::class,
			new FormsFinder( $this->store )
		);
	}

	public function testGetFormDefinitions() {

		$data[] = json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] );
		$data[] = json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ) ] ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Search\Form\FormsFinder' )
			->setConstructorArgs( [ $this->store ] )
			->setMethods( [ 'getNativeData' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'getNativeData' )
			->will( $this->onConsecutiveCalls( $data[0], $data[1] ) );

		$this->assertEquals(
			[
				'Foo' => [ 'Bar' => 42, 'Foobar' => 'test' ],
				1001,
				[ 'Foo' => 'Bar' ]
			],
			$instance->getFormDefinitions()
		);
	}

}
