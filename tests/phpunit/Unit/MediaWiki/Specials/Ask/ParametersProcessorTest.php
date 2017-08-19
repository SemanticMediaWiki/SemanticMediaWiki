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

	public function testParametersWithDefaults() {

		$request = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->at( 7 ) )
			->method( 'getVal' )
			->with(
				$this->equalTo( 'offset' ),
				$this->equalTo( 0 ) );

		$request->expects( $this->at( 8 ) )
			->method( 'getVal' )
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

}
