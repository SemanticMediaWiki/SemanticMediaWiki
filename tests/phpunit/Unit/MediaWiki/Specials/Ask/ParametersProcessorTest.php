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

}
