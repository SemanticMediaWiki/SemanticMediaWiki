<?php

namespace SMW\Tests\Integration;

use SMW\Tests\Utils\UtilityFactory;
use SMW\ApplicationFactory;
use SMW\Localizer;
use SMW\DIWikiPage;
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
class QueryProfileAnnotatorToProcessorIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
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

		$query->setSubject( DIWikiPage::newFromText( __METHOD__ ) );

		$queryProfileAnnotatorFactory = ApplicationFactory::getInstance()->newQueryProfileAnnotatorFactory();

		$jointProfileAnnotator = $queryProfileAnnotatorFactory->newJointProfileAnnotator(
			$query,
			$formattedParams['format']->getValue()
		);

		$jointProfileAnnotator->addAnnotation();

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$jointProfileAnnotator->getContainer()->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$jointProfileAnnotator->getContainer()->getSemanticData()
		);
	}

	public function queryDataProvider() {

		$categoryNS = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

		$provider = array();

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 1, 1, '[[Modification date::+]]' )
			)
		);

		// #1
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" )
			)
		);

		// #2 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = array(
			array(
				'',
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=bar'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'table', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" )
			)
		);

		return $provider;
	}

}
