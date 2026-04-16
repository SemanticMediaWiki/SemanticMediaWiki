<?php

namespace SMW\Tests\Unit\Property\Annotators;

use MediaWiki\MediaWikiServices;
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

	protected function setUp(): void {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
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

		MediaWikiServices::getInstance()->resetServiceForTesting( 'RestrictionStore' );

		MediaWikiServices::getInstance()->redefineService( 'RestrictionStore', function () {
			$restrictionStore = $this->getMockBuilder( RestrictionStore::class )
				->disableOriginalConstructor()
				->getMock();

			$restrictionStore->expects( $this->any() )
				->method( 'getRestrictions' )
				->willReturn( [ 'Foo' ] );

			return $restrictionStore;
		} );

		var_dump( 'test' );
		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
		var_dump( $restrictionStore->getRestrictions( $title, 'edit' ) );

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
