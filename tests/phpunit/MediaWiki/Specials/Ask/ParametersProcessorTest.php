<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Ask\ParametersProcessor;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ParametersProcessorTest extends TestCase {

	public function testEmpty() {
		$request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsArray(

			ParametersProcessor::process( $request, [] )
		);
	}

	public function testParameters() {
		$request = $this->getMockBuilder( WebRequest::class )
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
		$request = $this->getMockBuilder( WebRequest::class )
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
		$request = $this->getMockBuilder( WebRequest::class )
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
		$request = $this->getMockBuilder( WebRequest::class )
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
		$request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->exactly( 2 ) )
			->method( 'getInt' )
			->willReturnCallback( static function ( $key, $default = null ) {
				if ( $key === 'offset' ) {
					return $default;
				}
				if ( $key === 'limit' ) {
					return $default;
				}
			} );

		ParametersProcessor::setDefaultLimit( 42 );

		$parameters = [
			'[[Foo::bar]]',
			'|?Foo=foobar'
		];

		ParametersProcessor::process( $request, $parameters );
	}

	public function testParameters_Sort_FirstEmpty() {
		$request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->exactly( 2 ) )
			->method( 'getArray' )
			->willReturnCallback( static function ( $key ) {
				$map = [
					'sort_num'  => [ '', '', 'Foo' ],
					'order_num' => [ 'asc', 'desc' ],
				];
				return $map[$key] ?? [];
			} );

		$parameters = [
			'[[Foo::bar]]'
		];

		[ $q, $p, $po ] = ParametersProcessor::process(
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
		$request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->exactly( 2 ) )
			->method( 'getArray' )
			->willReturnCallback( static function ( $key ) {
				$map = [
					'sort_num'  => [ 'Foo', '' ],
					'order_num' => [ 'asc', 'desc' ],
				];
				return $map[$key] ?? [];
			} );

		$parameters = [
			'[[Foo::bar]]'
		];

		[ $q, $p, $po ] = ParametersProcessor::process(
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
		$request = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->atLeastOnce() )
			->method( 'getCheck' )
			->willReturnCallback( static function ( $key ) {
				return $key === 'q';
			} );

		$request->expects( $this->once() )
			->method( 'getVal' )
			->with( 'p' )
			->willReturn( '' );

		$request->expects( $this->atLeastOnce() )
			->method( 'getArray' )
			->willReturnCallback( static function ( $key ) {
				if ( $key === 'p' ) {
					return [ 'foo' => [ 'Bar', 'foobar' ] ];
				}
				return [];
			} );

		$parameters = [];

		$res = ParametersProcessor::process( $request, $parameters );

		$this->assertEquals(
			[
				'foo'    => 'Bar,foobar',
				'format' => 'broadtable',
				'offset' => 0,
				'limit'  => 0,
				'order'  => 'asc',
				'sort'   => '',
			],
			$res[1]
		);
	}

}
