<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParametersProcessor;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParametersProcessorTest extends \PHPUnit_Framework_TestCase {

	public function testEmpty() {

		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'array',
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
				$this->equalTo( 'offset' ),
				$this->equalTo( 0 ) );

		$request->expects( $this->at( 6 ) )
			->method( 'getInt' )
			->with(
				$this->equalTo( 'limit' ),
				$this->equalTo( 42 ) );

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
			->with( $this->equalTo( 'sort_num' ) )
			->will( $this->returnValue( [ '', '', 'Foo' ] ) );

		$request->expects( $this->at( 4 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'order_num' ) )
			->will( $this->returnValue( [ 'asc', 'desc' ] ) );

		$parameters = [
			'[[Foo::bar]]'
		];

		list( $q, $p, $po ) = ParametersProcessor::process(
			$request,
			$parameters
		);

		$this->assertSame(
			$p['sort'],
			',Foo'
		);

		$this->assertSame(
			$p['order'],
			'asc,desc'
		);
	}

	public function testParameters_Sort_FirstNotEmpty() {

		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 3 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'sort_num' ) )
			->will( $this->returnValue( [ 'Foo', '' ] ) );

		$request->expects( $this->at( 4 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'order_num' ) )
			->will( $this->returnValue( [ 'asc', 'desc' ] ) );

		$parameters = [
			'[[Foo::bar]]'
		];

		list( $q, $p, $po ) = ParametersProcessor::process(
			$request,
			$parameters
		);

		$this->assertSame(
			$p['sort'],
			'Foo'
		);

		$this->assertSame(
			$p['order'],
			'asc'
		);
	}

	public function testParametersOn_p_Array_Request() {

		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 0 ) )
			->method( 'getCheck' )
			->with( $this->equalTo( 'q' ) )
			->will( $this->returnValue( true ) );

		$request->expects( $this->at( 1 ) )
			->method( 'getVal' )
			->with( $this->equalTo( 'p' ) )
			->will( $this->returnValue( '' ) );

		$request->expects( $this->at( 2 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'p' ) )
			->will( $this->returnValue( [ 'foo' => [ 'Bar', 'foobar' ] ] ) );

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
