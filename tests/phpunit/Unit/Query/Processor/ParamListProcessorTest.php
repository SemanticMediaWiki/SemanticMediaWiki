<?php

namespace SMW\Tests\Query\Processor;

use SMW\Query\Processor\ParamListProcessor;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Query\Processor\ParamListProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParamListProcessorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$printRequestFactory = $this->getMockBuilder( '\SMW\Query\PrintRequestFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParamListProcessor::class,
			new ParamListProcessor( $printRequestFactory )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testPreprocess( $parameters, $showMode, $expected ) {

		$printRequestFactory = $this->getMockBuilder( '\SMW\Query\PrintRequestFactory' )
			->disableOriginalConstructor()
			->getMock();

			$instance = new ParamListProcessor(
				$printRequestFactory
			);

		$this->assertEquals(
			$expected,
			$instance->preprocess( $parameters, $showMode )
		);
	}

	/**
	 * @dataProvider legacyParametersProvider
	 */
	public function testLegacyArray( $parameters ) {

		$printRequestFactory = $this->getMockBuilder( '\SMW\Query\PrintRequestFactory' )
			->disableOriginalConstructor()
			->getMock();

			$instance = new ParamListProcessor(
				$printRequestFactory
			);

		$a = $instance->getLegacyArray(
			$parameters
		);

		$this->assertInternalType(
			'string',
			$a[0]
		);

		$this->assertInternalType(
			'array',
			$a[1]
		);

		$this->assertInternalType(
			'array',
			$a[2]
		);
	}

	public function parametersProvider() {

		yield [
			[ '[[Foo::Bar]]' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [],
				'parameters' => [],
				'this'       => []
			]
		];

		// #640
		yield [
			[ '[[Foo::Bar=Foobar]]' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar=Foobar]]',
				'printouts'  => [],
				'parameters' => [],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::&lt;Bar=Foobar&gt;]]' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::<Bar=Foobar>]]',
				'printouts'  => [],
				'parameters' => [],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::Bar]]', 'mainlabel=-' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [],
				'parameters' => [
					'mainlabel' => '-'
				],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::Bar]]', '?Foobar' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [
					'0bfab051cd82c364058617af13e9874a' => [
						'label'   => 'Foobar',
						'params'  => []
					]
				],
				'parameters' => [],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::Bar]]', '?Foobar', '+abc=123' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [
					'0bfab051cd82c364058617af13e9874a' => [
						'label'   => 'Foobar',
						'params'  => [
							'abc' => '123'
						]
					]
				],
				'parameters' => [],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::Bar]]', '?Foobar', '+abc=123', '+abc=123' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [
					'0bfab051cd82c364058617af13e9874a' => [
						'label'   => 'Foobar',
						'params'  => [
							'abc' => '123'
						]
					]
				],
				'parameters' => [],
				'this'       => []
			]
		];

		yield [
			[ '[[Foo::Bar]]', '?Foobar', '+abc=123', '?ABC', '+abc=456', '+abc=+FOO', 'limit=10' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [
					'0bfab051cd82c364058617af13e9874a' => [
						'label'   => 'Foobar',
						'params'  => [
							'abc' => '123'
						]
					],
					'2a30f08efdf827f7e76b895fde0fe670' => [
						'label'   => 'ABC',
						'params'  => [
							'abc' => '456',
							'abc' => '+FOO'
						]
					]
				],
				'parameters' => [
					'limit' => '10'
				],
				'this'       => []
			]
		];

		// mainlabel=Foo|+abc=123 is currently NOT supported
		yield [
			[ '[[Foo::Bar]]', 'mainlabel=Foo', '+abc=123' ],
			false,
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [],
				'parameters' => [
					'mainlabel' => 'Foo'
				],
				'this'       => []
			]
		];

		// #1645
		yield [
			[ 'Foo=Bar', 'link=none' ],
			true,
			[
				'showMode'   => true,
				'query'      => '[[:Foo=Bar]]',
				'printouts'  => [],
				'parameters' => [
					'link' => 'none'
				],
				'this'       => []
			]
		];

	}

	public function legacyParametersProvider() {

		yield [
			[
				'showMode'   => false,
				'query'      => '[[Foo::Bar]]',
				'printouts'  => [
					'0bfab051cd82c364058617af13e9874a' => [
						'label'   => 'Foobar',
						'params'  => [
							'abc' => '123'
						]
					],
					'2a30f08efdf827f7e76b895fde0fe670' => [
						'label'   => 'ABC',
						'params'  => [
							'abc' => '456',
							'abc' => '+FOO'
						]
					]
				],
				'parameters' => [
					'limit' => '10'
				],
				'this'       => []
			]
		];

		yield [
			[
				'showMode'   => true,
				'query'      => '[[:Foo=Bar]]',
				'printouts'  => [],
				'parameters' => [
					'link' => 'none'
				],
				'this'       => []
			]
		];

	}

}
