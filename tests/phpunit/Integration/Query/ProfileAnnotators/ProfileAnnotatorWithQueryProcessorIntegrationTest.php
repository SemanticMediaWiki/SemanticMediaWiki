<?php

namespace SMW\Tests\Integration\Query\ProfileAnnotators;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Tests\TestEnvironment;
use SMWQueryProcessor;

/**
 * @covers \SMWQueryProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ProfileAnnotatorWithQueryProcessorIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testCreateProfile( array $rawParams, array $expected ) {

		list( $query, $formattedParams ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		$query->setContextPage(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$profileAnnotatorFactory = ApplicationFactory::getInstance()->getQueryFactory()->newProfileAnnotatorFactory();

		$profileAnnotator = $profileAnnotatorFactory->newProfileAnnotator(
			$query,
			$formattedParams['format']->getValue()
		);

		$profileAnnotator->addAnnotation();

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$profileAnnotator->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$profileAnnotator->getSemanticData()
		);
	}

	public function queryDataProvider() {

		$categoryNS = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

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
