<?php

namespace SMW\Tests\Unit\Property\Annotators;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\EditProtectedPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectedPropertyAnnotatorTest extends TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$this->assertInstanceOf(
			EditProtectedPropertyAnnotator::class,
			$instance
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAddAnnotationForDisplayTitle(
		$title,
		$editProtectionRight,
		array $expected,
		bool $isProtected,
		?array $restrictions
	) {
		$this->testEnvironment->redefineMediaWikiService(
			'RestrictionStore',
			function () use ( $isProtected, $restrictions ) {
				$restrictionStore = $this->getMockBuilder( RestrictionStore::class )
					->disableOriginalConstructor()
					->getMock();

				$restrictionStore->expects( $this->any() )
					->method( 'isProtected' )
					->willReturn( $isProtected );

				if ( is_array( $restrictions ) ) {
					$restrictionStore->expects( $this->any() )
						->method( 'getRestrictions' )
						->willReturn( $restrictions );
				} else {
					$restrictionStore->expects( $this->never() )
						->method( 'getRestrictions' );
				}

				return $restrictionStore;
			}
		);

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
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setIndicator' );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->redefineMediaWikiService( 'RestrictionStore', function () {
			$restrictionStore = $this->getMockBuilder( RestrictionStore::class )
				->disableOriginalConstructor()
				->getMock();

			$restrictionStore->expects( $this->any() )
				->method( 'isProtected' )
				->willReturn( true );

			$restrictionStore->expects( $this->any() )
				->method( 'getRestrictions' )
				->willReturn( [ 'Foo' ] );

			return $restrictionStore;
		} );

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$instance->setEditProtectionRight( 'Foo' );
		$instance->addTopIndicatorTo( $parserOutput );
	}

	public function titleProvider() {
		$title = $this->getMockBuilder( Title::class )
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
			],
			true,
			[
				'Foo'
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
			],
			true,
			[
				'Foo'
			]
		];

		$title = $this->getMockBuilder( Title::class )
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
			],
			false
		];

		$title = $this->getMockBuilder( Title::class )
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
			],
			false
		];

		return $provider;
	}

}
