<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParametersProcessor;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParametersProcessorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testEmpty() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsArray(

			ParametersProcessor::process( $request, [] )
		);
	}

	public function testParameters() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=foobar'
		];

		$result = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			'[[Foo::bar]]',
			$result[0]
		);
	}

	public function testParameters_Printrequest_PlusPipe() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=Bar|+index=1'
		];

		$result = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			'Bar|+index=1',
			$result[1]['|?foo']
		);
	}

	public function testParameters_Printrequest_WikiLink() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=[[Some|other]]'
		];

		$result = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			'[[Some|other]]',
			$result[1]['|?foo']
		);
	}

	public function testParameters_Printrequest_WikiLink_PlusPipe() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=[[Some|other]]|+index=1'
		];

		$result = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			'[[Some|other]]|+index=1',
			$result[1]['|?foo']
		);
	}

	public function testParametersWithDefaults() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 5 ) )
			->method( 'getInt' )
			->with(
				'offset',
				0 );

		$request->expects( $this->at( 6 ) )
			->method( 'getInt' )
			->with(
				'limit',
				42 );

		ParametersProcessor::setDefaultLimit( 42 );

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=foobar'
		];

		ParametersProcessor::process( $request, $parameters );
	}

	public function testParameters_Sort_FirstEmpty() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 3 ) )
			->method( 'getArray' )
			->with( 'sort_num' )
			->willReturn( [ '', '', 'Foo' ] );

		$request->expects( $this->at( 4 ) )
			->method( 'getArray' )
			->with( 'order_num' )
			->willReturn( [ 'asc', 'desc' ] );

		$parameters = [
			'[[Foo::bar]]'
		];

		list( $q, $p, $po ) = ParametersProcessor::process(
			$request,
			$parameters
		);

		$this->assertSame(
			',Foo',
			$p['sort']
		);

		$this->assertSame(
			'asc,desc',
			$p['order']
		);
	}

	public function testParameters_Sort_FirstNotEmpty() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 3 ) )
			->method( 'getArray' )
			->with( 'sort_num' )
			->willReturn( [ 'Foo', '' ] );

		$request->expects( $this->at( 4 ) )
			->method( 'getArray' )
			->with( 'order_num' )
			->willReturn( [ 'asc', 'desc' ] );

		$parameters = [
			'[[Foo::bar]]'
		];

		list( $q, $p, $po ) = ParametersProcessor::process(
			$request,
			$parameters
		);

		$this->assertSame(
			'Foo',
			$p['sort']
		);

		$this->assertSame(
			'asc',
			$p['order']
		);
	}

	public function testParametersOn_p_Array_Request() {
		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 0 ) )
			->method( 'getCheck' )
			->with( 'q' )
			->willReturn( true );

		$request->expects( $this->at( 1 ) )
			->method( 'getVal' )
			->with( 'p' )
			->willReturn( '' );

		$request->expects( $this->at( 2 ) )
			->method( 'getArray' )
			->with( 'p' )
			->willReturn( [ 'foo' => [ 'Bar', 'foobar' ] ] );

		$parameters = [];

		$res = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			[
				'foo'    => 'Bar,foobar',
				'format' => 'broadtable',
				'offset' => null,
				'limit'  => null
			],
			$res[1]
		);
	}

}
