<?php

namespace SMW\Test;

use SMw\SemanticData;
use SMW\ParserData;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class PageWithTemplateInclusionRegressionTest extends MwImporterTestBase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'PageWithTemplateInclusionRegressionTest-Mw-1-19-7.xml';
	}

	public function getTitles() {
		return array(
			'Foo-1-19-7',
			'Template:FooAsk',
			'Template:FooShow',
			'Template:FooSubobject',
			'Template:FooTemplate'
		);
	}

	public function assertSemanticData() {

		$title = Title::newFromText( 'Foo-1-19-7' );
		$semanticData = $this->fetchSemanticDataFromOutput( $title );

		$expectedProperties = array(
			'propertyKey' => array(
 				'Foo',
 				'Quux',
				'_ASK',
				'_LEDT',
				'_MDAT',
				'_SKEY',
				'_SOBJ',
				'_INST'
			)
		);

		$this->assertPropertiesAreSet( $expectedProperties, $semanticData );
	}

}
