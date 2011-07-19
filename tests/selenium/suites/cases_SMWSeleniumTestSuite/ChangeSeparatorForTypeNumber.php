<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class ChangeSeparatorForTypeNumber extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:AnyNewNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=exact:Property:AnyNewNumber");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has type::Number]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "Property:AnyOtherNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=exact:Property:AnyOtherNumber");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has type::Number]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestAnyNewNumber1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestAnyNewNumber1");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[AnyNewNumber::445000.000]] __SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isTextPresent("445,000"));
		
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/MediaWiki:Smw_kiloseparator");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", ".");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "MediaWiki:Smw_decseparator");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", ",");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	public function testTest2()
	{
		$this->open($this->getUrl() ."index.php/TestAnyNewNumber1");

			$this->assertTrue($this->isTextPresent("445.000.000"));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/TestAnyNewNumber1");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "Property:AnyNewNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "Property:AnyOtherNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->open($this->getUrl() ."index.php/MediaWiki:Smw_kiloseparator");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->open($this->getUrl() ."index.php/MediaWiki:smw_decseparator");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
	}
}
