<?php

namespace SMW\Test;

use SMW\ContentProcessor;
use SMW\DIProperty;
use SMW\ExtensionContext;
use SMW\ParserData;

use ParserOutput;
use Title;

/**
 * @covers \SMW\ContentProcessor
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class ContentProcessorCategoryAnnotationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Issue #241
	 */
	public function testCategoryAnnotationForBothColonIdentifier() {

		$text = '[[Category:SingleColonNotion]], [[Category::DoubleColonNotion]]';

		$expected= array(
			'propertyCount'  => 1,
			'propertyties'   => array( new DIProperty( '_INST' ) ),
			'propertyValues' => array( 'SingleColonNotion', 'DoubleColonNotion' )
		);

		$context = new ExtensionContext;

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput
		);

		$contentProcessor = new ContentProcessor( $parserData, $context );
		$contentProcessor->parse( $text );

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

}
