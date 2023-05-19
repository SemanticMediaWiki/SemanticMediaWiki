<?php

namespace SMW\Tests;

use FauxRequest;
use Language;
use MediaWiki\MediaWikiServices;
use RequestContext;
use SpecialPage;
use SpecialPageFactory;

/**
 * Tests for registered special pages
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * @covers \SMW\SpecialWantedProperties
 * @covers \SMW\SpecialUnusedProperties
 * @covers \SMW\SpecialProperties
 * @covers \SMW\SpecialConcepts
 * @covers \SMW\SpecialPage
 * @covers \SMW\MediaWiki\Specials\SpecialAsk
 * @covers \SMW\MediaWiki\Specials\SpecialAdmin
 * @covers \SMW\MediaWiki\Specials\SpecialBrowse
 * @covers \SMW\MediaWiki\Specials\SpecialSearchByProperty
 *
 * @note Test base was borrowed from the EducationProgram extension
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 */
class SpecialsTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @dataProvider specialPageProvider
	 *
	 * @param $specialPage
	 */
	public function testSpecial( SpecialPage $specialPage ) {

		try {
			$specialPage->execute( '' );
		}
		catch ( \Exception $exception ) {
			if ( !( $exception instanceof \PermissionsError ) && !( $exception instanceof \ErrorPageError ) ) {
				throw $exception;
			}
		}

		$this->assertTrue( true, 'SpecialPage test did run without errors' );
	}

	/**
	 * @test SpecialPageFactory::getLocalNameFor
	 * @dataProvider specialPageProvider
	 *
	 * Test created in response to bug 44191
	 *
	 * @param $specialPage
	 */
	public function testSpecialAliasesContLang( SpecialPage $specialPage ) {

		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();

		// Test for languages
		$langCodes = [ 'en', 'fr', 'de', 'es', 'zh', 'ja' ];

		// Test aliases for a specific language
		foreach ( $langCodes as $langCode ) {
			$langObj = $languageFactory->getLanguage( $langCode );

			$aliases = $langObj->getSpecialPageAliases();
			$found = false;
			$name = $specialPage->getName();

			// Check against available aliases
			foreach ( $aliases as $n => $values ) {
				foreach ( $values as $value ) {
					if( $name === $value ) {
						$found = true;
						break;
					}
				}
			}

			$this->assertTrue(
				$found,
				"{$name} alias not found in language {$langCode}"
			);
		}
	}

	/**
	 * Provides special pages
	 *
	 * @return array
	 */
	public function specialPageProvider() {
		$request = new FauxRequest( [], true );
		$argLists = [];

		$specialPages = [
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'ExportRDF',
			'Types',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
			'Concepts',
			'ProcessingErrorList',
			'PropertyLabelSimilarity',
			'URIResolver'
		];

		foreach ( $specialPages as $special ) {

			$specialPage = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage(
				$special
			);

			$context = RequestContext::newExtraneousContext( $specialPage->getPageTitle() );
			$context->setRequest( $request );

			$specialPage->setContext( clone $context );
			$argLists[] = [ clone $specialPage ];

			$context->setUser( $this->getUser() );
			$specialPage->setContext( $context );
			$argLists[] = [ $specialPage ];
		}

		return $argLists;
	}
}
