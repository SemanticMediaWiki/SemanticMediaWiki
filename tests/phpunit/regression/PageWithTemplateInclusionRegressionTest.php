<?php

namespace SMW\Test;

use SMW\DIProperty;

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
 * @since 1.9.1
 *
 * @author mwjames
 */
class PageWithTemplateInclusionRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'PageWithTemplateInclusionRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'Foo-1-19-7',
			'Template:FooAsk',
			'Template:FooShow',
			'Template:FooSubobject',
			'Template:FooTemplate'
		);
	}

	public function assertDataImport() {

		$expectedProperties = array(
			'properties' => array(
				DIProperty::newFromUserLabel( 'Foo' ),
				DIProperty::newFromUserLabel( 'Quux' ),
				new DIProperty( '_ASK' ),
				new DIProperty( '_MDAT' ),
				new DIProperty( '_SKEY' ),
				new DIProperty( '_SOBJ' ),
				new DIProperty( '_INST' )
			)
		);

		$title = Title::newFromText( 'Foo-1-19-7' );
		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataFinder = new ByPageSemanticDataFinder;
		$semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expectedProperties,
			$semanticDataFinder->fetchFromOutput()
		);
	}

}
