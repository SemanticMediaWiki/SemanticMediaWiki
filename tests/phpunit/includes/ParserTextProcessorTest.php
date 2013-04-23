<?php

namespace SMW\Test;

use SMW\ParserTextProcessor;
use SMW\ParserData;
use SMW\Settings;

use SMWDIProperty;
use SMWDataItem;
use SMWDataValueFactory;
use Title;
use MWException;
use ParserOutput;
use ReflectionClass;

/**
 * Tests for the SMW\ParserTextProcessor class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup SMWParser
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Testing methods provided by the ParserTextProcessor class
 *
 * @ingroup SMW
 */
class ParserTextProcessorTest extends \MediaWikiTestCase {

	protected $className = 'SMW\ParserTextProcessor';

	/**
	 * Provides text samples
	 *
	 * @return array
	 */
	public function getTextDataProvider() {
		return array(

			// #0 NS_MAIN; [[FooBar...]] with a different caption
			array(
				NS_MAIN,
				array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors' => true,
				),
				'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
				' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
				array(
					'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
						' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
						' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
						' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
					'propertyCount' => 3,
					'propertyLabel' => array( 'Foo', 'Bar', 'FooBar' ),
					'propertyValue' => array( 'Dictumst', 'Tincidunt semper', '9001' )
				)
			),

			// #1 NS_MAIN; [[FooBar...]] with a different caption and smwgLinksInValues = true
			array(
				NS_MAIN,
				array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => true,
					'smwgInlineErrors' => true,
				),
				'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
				' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::[[tincidunt semper]]]] facilisi dolor Aenean. Ut' .
				' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::[http:://www/foo/9001] ]] et Donec.',
				array(
					'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
						' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
						' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
						' Aliquam {{volutpat}} arcu ultrices eu Ut quis'.
						' [[:Http:://www/foo/9001|http:://www/foo/9001]] et Donec.',
					'propertyCount' => 3,
					'propertyLabel' => array( 'Foo', 'Bar', 'FooBar' ),
					'propertyValue' => array( 'Dictumst', 'Tincidunt semper', 'Http:://www/foo/9001' )
				)
			),

			// #1 NS_MAIN, [[-FooBar...]] produces an error with inlineErrors = true
			// (only check for an indication of an error in 'resultText' )
			array(
				NS_MAIN,
				array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors' => true,
				),
				'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
				' [[-FooBar::dictumst|重い]] cursus. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
				array(
					'resultText'    => 'class="smw-highlighter" data-type="4" data-state="inline"',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Foo', 'Bar' ),
					'propertyValue' => array( 'Tincidunt semper', '9001' )
				)
			),

			// #2 NS_MAIN, [[-FooBar...]] produces an error but inlineErrors = false
			array(
				NS_MAIN,
				array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors' => false,
				),
				'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
				' [[-FooBar::dictumst|軽い]] cursus. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
				array(
					'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
						' 軽い cursus. Nisl sit condimentum Quisque facilisis' .
						' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
						' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Foo', 'Bar' ),
					'propertyValue' => array( 'Tincidunt semper', '9001' )
				)
			),

			// #3 NS_HELP disabled
			array(
				NS_HELP,
				array(
					'smwgNamespacesWithSemanticLinks' => array( NS_HELP => false ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors' => true,
				),
				'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
				' [[FooBar::dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
				array(
					'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
						' [[:Dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
						' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
						' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
					'propertyCount' => 0,
					'propertyLabel' => array(),
					'propertyValue' => array()
				)
			),
		);
	}

	/**
	 * Provides magic words sample text
	 *
	 * @return array
	 */
	public function getMagicWordDataProvider() {
		return array(
			// #0 __NOFACTBOX__
			array(
				NS_MAIN,
				'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
				' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' __NOFACTBOX__ ',
				array( 'SMW_NOFACTBOX' )
			),

			// #1 __SHOWFACTBOX__
			array(
				NS_HELP,
				'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
				' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' __SHOWFACTBOX__',
				array( 'SMW_SHOWFACTBOX' )
			),
		);
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 *
	 * @return string
	 */
	private function getRandomString( $length = 10 ) {
		return substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Helper method that returns a Title object
	 *
	 * @param $namespace
	 *
	 * @return Title
	 */
	private function getTitle( $namespace = NS_MAIN ){
		return Title::newFromText( $this->getRandomString(), $namespace );
	}

	/**
	 * Helper method that returns a ParserOutput object
	 *
	 * @return ParserOutput
	 */
	private function getParserOutput(){
		return new ParserOutput();
	}

	/**
	 * Helper method that returns a ParserData object
	 *
	 * @param $title
	 * @param $parserOutput
	 *
	 * @return ParserData
	 */
	private function getParserData( Title $title, ParserOutput $parserOutput ){
		return new ParserData( $title, $parserOutput );
	}

	/**
	 * Helper method that returns a ParserTextProcessor object
	 *
	 * @param $title
	 * @param $parserOutput
	 * @param $settings
	 *
	 * @return ParserTextProcessor
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {
		return new ParserTextProcessor(
			$this->getParserData( $title, $parserOutput ),
			Settings::newFromArray( $settings )
		);
	}

	/**
	 * @test ParserTextProcessor::__construct
	 * @dataProvider getTextDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 */
	public function testConstructor( $namespace ) {
		$instance = $this->getInstance( $this->getTitle( $namespace ), $this->getParserOutput() );
		$this->assertInstanceOf( $this->className, $instance );
	}

	/**
	 * @test ParserTextProcessor::getRDFUrl
	 *
	 * @since 1.9
	 */
	public function testGetRDFUrl() {
		$reflection = new ReflectionClass( $this->className );

		$parserOutput = $this->getParserOutput();
		$title = $this->getTitle();
		$instance =	$this->getInstance( $title, $parserOutput );

		// Make proected method accessible
		$method = $reflection->getMethod( 'getRDFUrl' );
		$method->setAccessible( true );

		// Invoke the instance
		$result = $method->invoke( $instance, $title );

		// Doing a lazy check, the url has to contain the title text
		$this->assertInternalType( 'string', $result );
		$this->assertContains( 'title="' . $title->getText() . '"', $result );
	}

	/**
	 * @test ParserTextProcessor::stripMagicWords
	 * @dataProvider getMagicWordDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 * @param $text
	 * @param $expected
	 */
	public function testStripMagicWords( $namespace, $text, array $expected ) {
		$reflection = new ReflectionClass( $this->className );

		$parserOutput = $this->getParserOutput();
		$title = $this->getTitle( $namespace );
		$instance =	$this->getInstance( $title, $parserOutput );

		// Make protected method accessible
		$method = $reflection->getMethod( 'stripMagicWords' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, array( &$text ) );

		// Check return values
		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

		// Check values against ParserData/ParserOutput object
		$parserData = $this->getParserData( $title, $parserOutput );

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			$this->assertEquals( $expected, $parserData->getOutput()->getExtensionData( 'smwmagicwords' ) );
		} else {
			$this->assertEquals( $expected, $parserData->getOutput()->mSMWMagicWords );
		}
	}

	/**
	 * @test ParserTextProcessor::parse
	 * @dataProvider getTextDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 * @param $settings
	 * @param $text
	 * @param $expected
	 */
	public function testParse( $namespace, array $settings, $text, array $expected ) {
		$parserOutput =  $this->getParserOutput();
		$title = $this->getTitle( $namespace );
		$instance = $this->getInstance( $title, $parserOutput, $settings );

		// Text parsing
		$instance->parse( $text );

		// Check transformed text
		$this->assertContains( $expected['resultText'], $text );

		// Re-read data from stored parserOutput
		$parserData = $this->getParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );
		$this->assertCount( $expected['propertyCount'], $parserData->getData()->getProperties() );

		// Check added properties
		foreach ( $parserData->getData()->getProperties() as $key => $diproperty ){
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
			$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );

			// Check added property values
			foreach ( $parserData->getData()->getPropertyValues( $diproperty ) as $dataItem ){
				$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
				if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				}
			}
		}
	}
}
