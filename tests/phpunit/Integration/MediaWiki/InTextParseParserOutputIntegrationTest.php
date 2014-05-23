<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ParserFactory;

use SMW\ContentParser;
use SMW\ParserData;

use Title;
use Parser;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9.1.1
 *
 * @author mwjames
 */
class InTextParseParserOutputIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextParse( $parameters, $expected ) {

		$instance = new ContentParser(
			$parameters['title'],
			ParserFactory::newFromTitle( $parameters['title'] )
		);

		$instance->parse( $parameters['text'] );

		$this->assertInstanceAfterParse( $instance );

		$this->assertSemanticDataAfterParse(
			$instance,
			$expected
		);
	}

	protected function assertInstanceAfterParse( $instance ) {
		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		$this->assertInternalType( 'string', $instance->getOutput()->getText() );
	}

	protected function assertSemanticDataAfterParse( $instance, $expected ) {

		$parserData = new ParserData( $instance->getTitle(), $instance->getOutput() );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$title = Title::newFromText( __METHOD__ );

		$provider = array();

		// #0 Empty
		$provider[] = array(
			array(
				'text'  => '',
				'title' => $title
			),
			array(
				'propertyCount' => 0
			)
		);

		// #1 With a single category
		$provider[] = array(
			array(
				'text'  => '[[Category:Foo]]',
				'title' => $title
			),
			array(
				'propertyCount'  => 2,
				'propertyKey'    => array( '_INST', '_SKEY' ),
				'propertyValues' => array( 'Foo', $title->getText() )
			)
		);

		// #2 With a sortkey
		$provider[] = array(
			array(
				'text'  => '{{DEFAULTSORTKEY:Bar}}',
				'title' => $title
			),
			array(
				'propertyCount'  => 1,
				'propertyKey'    => '_SKEY',
				'propertyValues' => array( 'Bar' )
			)
		);

		// #3 Combined
		$provider[] = array(
			array(
				'text'  => '[[Fuyu::Natsu]], [[Category:Foo]], {{DEFAULTSORTKEY:Bar}}',
				'title' => $title
			),
			array(
				'propertyCount'  => 3,
				'propertyKey'    => array( '_SKEY', '_INST', 'Fuyu' ),
				'propertyValues' => array( 'Bar', 'Foo', 'Natsu' )
			)
		);

		return $provider;
	}

}
