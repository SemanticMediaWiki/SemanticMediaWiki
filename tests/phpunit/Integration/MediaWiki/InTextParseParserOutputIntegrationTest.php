<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Utils\Validators\SemanticDataValidator;
use SMW\Tests\Utils\ParserFactory;

use SMW\ContentParser;
use SMW\ParserData;

use Title;
use Parser;

/**
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

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextParseForDisabledCapitalLinks( $parameters, $expected ) {

		$wgCapitalLinks = $GLOBALS['wgCapitalLinks'];
		$GLOBALS['wgCapitalLinks'] = false;

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

		$GLOBALS['wgCapitalLinks'] = $wgCapitalLinks;
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

		// #4 SMW_NS_PROPERTY
		$provider[] = array(
			array(
				'text'  => '[[has type::number]], {{#set:|has type=boolean}}, [[has Type::Page]] {{DEFAULTSORTKEY:Foo_Bar}}',
				'title' => Title::newFromText( __METHOD__, SMW_NS_PROPERTY )
			),
			array(
				'propertyCount'  => 3,
				'propertyKey'    => array( '_SKEY', 'Has type', 'has Type' ),
				'propertyValues' => array( 'Foo_Bar', 'Number', 'Boolean', 'Page' )
			)
		);

		return $provider;
	}

}
