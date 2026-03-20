<?php

namespace SMW\Tests\Property\Annotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\RedirectPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Property\Annotators\RedirectPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotatorTest extends TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( RedirectTargetFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder
		);

		$this->assertInstanceOf(
			RedirectPropertyAnnotator::class,
			$instance
		);
	}

	/**
	 * @dataProvider redirectsDataProvider
	 */
	public function testAddAnnotation( array $parameter, array $expected ) {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder->findRedirectTargetFromText( $parameter['text'] )
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function redirectsDataProvider() {
		// #0 Free text
		$provider[] = [
			[ 'text' => '#REDIRECT [[:Lala]]' ],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_REDI',
				'propertyValues' => ':Lala'
			]
		];

		// #1 Free text
		$provider[] = [
			[ 'text' => '#REDIRECT [[Lala]]' ],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_REDI',
				'propertyValues' => ':Lala'
			]
		];

		// #2 Invalid free text
		$provider[] = [
			[ 'text' => '#REDIR [[:Lala]]' ],
			[
				'propertyCount' => 0,
			]
		];

		// #3 Empty
		$provider[] = [
			[ 'text' => '' ],
			[
				'propertyCount' => 0,
			]
		];

		return $provider;
	}

}
