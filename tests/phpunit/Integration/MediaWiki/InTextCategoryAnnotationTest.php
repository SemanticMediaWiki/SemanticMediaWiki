<?php

namespace SMW\Tests\Integration;

use SMW\Tests\Utils\Validators\SemanticDataValidator;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\ParserData;

use ParserOutput;
use Title;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class InTextCategoryAnnotationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Issue #241
	 */
	public function testCategoryAnnotationForBothColonIdentifier() {

		$text = '[[Category:SingleColonNotion]], [[Category::DoubleColonNotion]]';

		$expected = array(
			'propertyCount'  => 1,
			'propertyties'   => array( new DIProperty( '_INST' ), new DIProperty( 'Category' ) ),
			'propertyValues' => array( 'SingleColonNotion', 'DoubleColonNotion' )
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = ApplicationFactory::getInstance()->newInTextAnnotationParser( $parserData );
		$instance->parse( $text );

		$semanticDataValidator = new SemanticDataValidator();

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

}
