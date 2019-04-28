<?php

namespace SMW\Tests\Constraint;

use SMW\Constraint\ConstraintErrorIndicatorProvider;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Constraint\ConstraintErrorIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $errorLookup;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->errorLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ErrorLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->will( $this->returnValue( $this->errorLookup ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintErrorIndicatorProvider::class,
			new ConstraintErrorIndicatorProvider( $this->store )
		);
	}

	public function testGetModules() {

		$instance = new ConstraintErrorIndicatorProvider(
			$this->store
		);

		$this->assertEmpty(
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$instance = new ConstraintErrorIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testCheckConstraintErrorIndicator() {

		$res = [
			(object)['o_blob' => 'Foo' ],
			(object)[ 'o_blob' => null, 'o_hash' => 'Bar' ]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$this->errorLookup->expects( $this->once() )
			->method( 'findErrorsByType' )
			->will( $this->returnValue( $res ) );

		$instance = new ConstraintErrorIndicatorProvider(
			$this->store
		);

		$options = [
			'action' => 'foo',
			'diff' => null
		];

		$this->assertTrue(
			$instance->hasIndicator( $title, $options )
		);

		$res = $instance->getIndicators();

		$this->assertArrayHasKey(
			'smw-w-constraint',
			$res
		);
	}

	public function testNoCheckOnNonExistingTitle() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$instance = new ConstraintErrorIndicatorProvider(
			$this->store
		);

		$this->assertFalse(
			$instance->hasIndicator( $title, [] )
		);

		$this->assertEmpty(
			$instance->getIndicators()
		);
	}

}
