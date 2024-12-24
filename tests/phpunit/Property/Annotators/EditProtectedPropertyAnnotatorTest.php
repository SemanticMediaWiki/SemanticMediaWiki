<?php

namespace SMW\Tests\Property\Annotators;

use MediaWiki\Permissions\RestrictionStore;
use SMW\DataItemFactory;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\Property\Annotators\EditProtectedPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectedPropertyAnnotatorTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		if ( method_exists( RestrictionStore::class, 'isProtected' ) ) {
			$this->markTestSkipped( 'SUT needs refactoring for RestrictionStore' );
		}

		$testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\EditProtectedPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAddAnnotationForDisplayTitle( $title, $editProtectionRight, array $expected ) {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$title
		);

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$instance->setEditProtectionRight( $editProtectionRight );
		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testAddTopIndicatorToFromMatchableRestriction() {
		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setIndicator' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->willReturn( [ 'Foo' ] );

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$instance->setEditProtectionRight( 'Foo' );
		$instance->addTopIndicatorTo( $parserOutput );
	}

	public function titleProvider() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		$provider = [];

		# 0 no EditProtectionRight
		$provider[] = [
			$title,
			false,
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		# 1
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		# 2
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 1,
				'propertyKeys'   => [ '_EDIP' ],
				'propertyValues' => [ true ],
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		# 3
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		return $provider;
	}

}
