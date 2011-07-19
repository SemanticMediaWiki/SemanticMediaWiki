<?php
/**
 * 
 * This test suite is made for a standard MediaWiki and Semantic MediaWiki installation. 
 * 
 * For a description of how to use a Selenium test suite for SMW,
 * see http://www.semantic-mediawiki.org/wiki/SMW_System_Testing_with_Selenium
 * 
 * Prerequisites
 * => Empty wiki (pages may also have been deleted, simply)
 * => Vector Skin
 * 
 * @author Benedikt Kämpgen, Jonas Wäckerle
 *
 */
class SMWSeleniumTestSuite extends SeleniumTestSuite
{
	public function setUp() {
		$this->setLoginBeforeTests( true );
		parent::setUp();
	}
	public function addTests() {
		$testFiles = array(
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/AvoidPropertyCreationTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/CreatePropertyPageTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/DefineAllowedValuesTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/DefineTypeOfPropertyTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/MakePropertySubpropertyOfAnotherOneTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/PropertiesListedOnSpecialPageTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/PropertyTypePageIsDefaultTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/SearchByPropertyWithNumericValueTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/SearchByPropertyWithStringValueTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/ShowFactboxTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/UnusedPropertiesOnSpecialPageTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/WantedPropertiesOnSpecialPageTestCase.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/BuildChainOfPropertiesInAQuery.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/EmbedValueWithShowParserFunction.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/RefreshLinkForEveryPage.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/SelectPagesByPropertyAnnotationByWildcard.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/SelectPagesByPropertyValue.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/RefreshInlineQuery.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/BuildInTypeNumber.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/ChangeSeparatorForTypeNumber.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/CopyQuerySyntaxFromSpecialAsk.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/FactboxLinksToSearchByProperty.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/FactboxLinksToSpecialBrowse.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/FactboxShowsProperties.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/SpecialAskProvidesGUIForQueries.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/ViewValuesOfPropertyOnSpecialPage.php',
			'extensions/SemanticMediaWiki/tests/selenium/suites/cases_SMWSeleniumTestSuite/AnnotatePageWithProperty.php'
			
		);
		parent::addTestFiles( $testFiles );
	}
}
