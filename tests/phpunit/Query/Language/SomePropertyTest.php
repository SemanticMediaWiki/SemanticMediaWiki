<?php

namespace SMW\Tests\Query\Language;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

/**
 * @covers \SMW\Query\Language\SomeProperty
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SomePropertyTest extends TestCase {

	public function testCanConstruct() {
		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			SomeProperty::class,
			new SomeProperty( $property, $description )
		);
	}

	/**
	 * @dataProvider somePropertyProvider
	 */
	public function testCommonMethods( $property, $description, $expected ) {
		$instance = new SomeProperty( $property, $description );

		$this->assertEquals(
			$expected['property'],
			$instance->getProperty()
		);

		$this->assertEquals(
			$expected['description'],
			$instance->getDescription()
		);

		$this->assertEquals(
			$expected['queryString'],
			$instance->getQueryString()
		);

		$this->assertEquals(
			$expected['queryStringAsValue'],
			$instance->getQueryString( true )
		);

		$this->assertEquals(
			$expected['isSingleton'],
			$instance->isSingleton()
		);

		$this->assertEquals(
			[],
			$instance->getPrintRequests()
		);

		$this->assertEquals(
			$expected['size'],
			$instance->getSize()
		);

		$this->assertEquals(
			$expected['depth'],
			$instance->getDepth()
		);

		$this->assertEquals(
			$expected['queryFeatures'],
			$instance->getQueryFeatures()
		);
	}

	/**
	 * @dataProvider comparativeHashProvider
	 */
	public function testGetFingerprint( $description, $compareTo, $expected ) {
		$this->assertEquals(
			$expected,
			$description->getFingerprint() === $compareTo->getFingerprint()
		);
	}

	public function somePropertyProvider() {
		# 0
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$provider[] = [
			$property,
			$description,
			[
				'property'    => $property,
				'description' => $description,
				'queryString' => "[[Foo::Bar]]",
				'queryStringAsValue' => "<q>[[Foo::Bar]]</q>",
				'isSingleton' => false,
				'queryFeatures' => 1,
				'size'  => 2,
				'depth' => 1
			]
		];

		# 1
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Yui' ),
			new ValueDescription( new WikiPage( 'Bar', NS_MAIN ), null )
		);

		$provider[] = [
			$property,
			$description,
			[
				'property'    => $property,
				'description' => $description,
				'queryString' => "[[Foo.Yui::Bar]]",
				'queryStringAsValue' => "<q>[[Foo.Yui::Bar]]</q>",
				'isSingleton' => false,
				'queryFeatures' => 1,
				'size'  => 3,
				'depth' => 2
			]
		];

		# 2
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Yui' ),
			new NamespaceDescription( NS_MAIN )
		);

		$provider[] = [
			$property,
			$description,
			[
				'property'    => $property,
				'description' => $description,
				'queryString' => "[[Foo.Yui:: <q>[[:+]]</q> ]]",
				'queryStringAsValue' => "<q>[[Foo.Yui:: <q>[[:+]]</q> ]]</q>",
				'isSingleton' => false,
				'queryFeatures' => 9,
				'size'  => 3,
				'depth' => 2
			]
		];

		# 3, 1096
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Yui' ),
			new SomeProperty(
				new Property( 'Bar', true ),
				new NamespaceDescription( NS_MAIN )
			)
		);

		$provider[] = [
			$property,
			$description,
			[
				'property'    => $property,
				'description' => $description,
				'queryString' => "[[Foo.Yui.-Bar:: <q>[[:+]]</q> ]]",
				'queryStringAsValue' => "<q>[[Foo.Yui.-Bar:: <q>[[:+]]</q> ]]</q>",
				'isSingleton' => false,
				'queryFeatures' => 9,
				'size'  => 4,
				'depth' => 3
			]
		];

		# 4, 1096
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Yui' ),
			new SomeProperty(
				new Property( '_SOBJ', true ),
				new NamespaceDescription( NS_MAIN )
			)
		);

		$provider[] = [
			$property,
			$description,
			[
				'property'    => $property,
				'description' => $description,
				'queryString' => "[[Foo.Yui.-Has subobject:: <q>[[:+]]</q> ]]",
				'queryStringAsValue' => "<q>[[Foo.Yui.-Has subobject:: <q>[[:+]]</q> ]]</q>",
				'isSingleton' => false,
				'queryFeatures' => 9,
				'size'  => 4,
				'depth' => 3
			]
		];

		return $provider;
	}

	public function testPrune() {
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$instance = new SomeProperty( $property, $description );

		$maxsize  = 2;
		$maxDepth = 2;
		$log      = [];

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);

		$this->assertSame( 0, $maxsize );
		$this->assertSame( 1, $maxDepth );

		$maxsize  = 0;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			new ThingDescription(),
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

	public function testStableFingerprint() {
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$instance = new SomeProperty(
			$property,
			$description
		);

		$this->assertSame(
			'S:8c2cab8d14dcd45d49aadb7fb5ab44a7',
			$instance->getFingerprint()
		);
	}

	public function testHierarchyDepthToBeCeiledOnMaxQSubpropertyDepthSetting() {
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$instance = new SomeProperty(
			$property,
			$description
		);

		$instance->setHierarchyDepth( 9999999 );

		$this->assertSame(
			$GLOBALS['smwgQSubpropertyDepth'],
			$instance->getHierarchyDepth()
		);
	}

	public function testGetQueryStringWithHierarchyDepth() {
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$instance = new SomeProperty(
			$property,
			$description
		);

		$instance->setHierarchyDepth( 1 );

		$this->assertSame(
			"[[Foo::Bar|+depth=1]]",
			$instance->getQueryString()
		);
	}

	public function testVaryingHierarchyDepthCausesDifferentFingerprint() {
		$property = new Property( 'Foo' );

		$description = new ValueDescription(
			new WikiPage( 'Bar', NS_MAIN ),
			$property
		);

		$instance = new SomeProperty(
			$property,
			$description
		);

		$instance->setHierarchyDepth( 9999 );
		$expected = $instance->getFingerprint();

		$instance = new SomeProperty(
			$property,
			$description
		);

		$this->assertNotSame(
			$expected,
			$instance->getFingerprint()
		);
	}

	public function comparativeHashProvider() {
		// Same property, different description === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo' ),
				new NamespaceDescription( NS_HELP )
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new NamespaceDescription( NS_MAIN )
			),
			false
		];

		// Inverse property, same description === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo', true ),
				new NamespaceDescription( NS_MAIN )
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new NamespaceDescription( NS_MAIN )
			),
			false
		];

		// Same property, different description === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo', true ),
				new NamespaceDescription( NS_MAIN )
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new ThingDescription()
			),
			false
		];

		// Property.chain, different description === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo', true ),
				new ThingDescription()
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new ThingDescription()
				)
			),
			false
		];

		// Property.chain, same description === same hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new ThingDescription()
				)
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new ThingDescription()
				)
			),
			true
		];

		// Property.chain, different description (inverse prop) === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new ThingDescription()
				)
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo', true ),
					new ThingDescription()
				)
			),
			false
		];

		// Property.chain, different description === different hash
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo' ),
				new ThingDescription()
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new SomeProperty(
						new Property( 'Foo' ),
						new ThingDescription()
					)
				)
			),
			false
		];

		// Property.chain, different description === different hash
		// "[[Foo.Foo::Foo]]" !== "[[Foo.Foo.Foo::Foo]]"
		$provider[] = [
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new SomeProperty(
						new Property( 'Foo' ),
						new ValueDescription( new WikiPage( 'Foo', NS_MAIN ) )
					)
				)
			),
			new SomeProperty(
				new Property( 'Foo' ),
				new SomeProperty(
					new Property( 'Foo' ),
					new SomeProperty(
						new Property( 'Foo' ),
						new SomeProperty(
							new Property( 'Foo' ),
							new ValueDescription( new WikiPage( 'Foo', NS_MAIN ) )
						)
					)
				)
			),
			false
		];

		return $provider;
	}

}
