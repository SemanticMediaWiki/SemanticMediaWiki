<?php

namespace SMW\Tests\Integration;

use FauxRequest;
use MediaWiki\MediaWikiServices;
use RequestContext;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SpecialPage;

/**
 * Tests for registered special pages
 *
 * @file
 *
 * @license GPL-2.0-or-later
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
 * @group Database
 * @group medium
 */
class SpecialsTest extends SMWIntegrationTestCase {

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
	 * @param $specialPageProvider
	 */
	public function testSpecial( callable $specialPageProvider ) {
		try {
			$specialPageProvider()->execute( '' );
		} catch ( \Exception $exception ) {
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
	 * @param $specialPageProvider
	 */
	public function testSpecialAliasesContLang( callable $specialPageProvider ) {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();

		// Test for languages
		$langCodes = [ 'en', 'fr', 'de', 'es', 'zh', 'ja' ];

		// Test aliases for a specific language
		foreach ( $langCodes as $langCode ) {
			$langObj = $languageFactory->getLanguage( $langCode );

			$aliases = $langObj->getSpecialPageAliases();
			$found = false;
			$name = $specialPageProvider()->getName();

			// Check against available aliases
			foreach ( $aliases as $n => $values ) {
				foreach ( $values as $value ) {
					if ( $name === $value ) {
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
			// Defer instantiating the special pages until the test runs
			// to avoid prematurely capturing service references that may become stale later.
			$specialPageCallable = static function () use ( $special, $request ): SpecialPage {
				$specialPage = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage(
					$special
				);

				$context = RequestContext::newExtraneousContext( $specialPage->getPageTitle() );
				$context->setRequest( $request );
				$specialPage->setContext( $context );

				return $specialPage;
			};

			$specialPageWithSuperUserCallable = static function () use ( $special, $request ): SpecialPage {
				$specialPage = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage(
					$special
				);

				$context = RequestContext::newExtraneousContext( $specialPage->getPageTitle() );
				$context->setRequest( $request );
				$context->setUser( new MockSuperUser() );
				$specialPage->setContext( $context );

				return $specialPage;
			};

			$argLists[] = [ $specialPageCallable ];

			$argLists[] = [ $specialPageWithSuperUserCallable ];
		}

		return $argLists;
	}
}
