<?php

namespace SMW\Tests\Integration\Query\ProfileAnnotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Localizer\Localizer;
use SMW\Query\QueryProcessor;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMWQueryProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ProfileAnnotatorWithQueryProcessorIntegrationTest extends TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		if ( $GLOBALS['wgLanguageCode'] !== 'en' ) {
			$this->markTestSkipped( 'Category title produces different fingerprint!' );
		}

		$testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testCreateProfile( array $rawParams, array $expected ) {
		[ $query, $formattedParams ] = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			QueryProcessor::INLINE_QUERY,
			false
		);

		$query->setContextPage(
			WikiPage::newFromText( __METHOD__ )
		);

		$profileAnnotatorFactory = ApplicationFactory::getInstance()->getQueryFactory()->newProfileAnnotatorFactory();

		$profileAnnotator = $profileAnnotatorFactory->newProfileAnnotator(
			$query,
			$formattedParams['format']->getValue()
		);

		$profileAnnotator->addAnnotation();

		$this->assertInstanceOf(
			SemanticData::class,
			$profileAnnotator->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$profileAnnotator->getSemanticData()
		);
	}

	public function queryDataProvider() {
		$categoryNS = Localizer::getInstance()->getNsText( NS_CATEGORY );

		$provider = [];

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = [
			[
				'',
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'list', 1, 1, '[[Modification date::+]]' ]
			]
		];

		// #1
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = [
			[
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'list', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" ]
			]
		];

		// #2 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = [
			[
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=bar'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'table', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" ]
			]
		];

		return $provider;
	}

}
