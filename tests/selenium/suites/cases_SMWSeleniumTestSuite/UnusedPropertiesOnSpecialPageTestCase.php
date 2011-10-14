<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class UnusedPropertiesOnSpecialPageTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:NoPageWillUseMe");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:NoPageWillUseMe");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This is a test property...");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "UseProp");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=UseProp");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[NoPageWillUseMe::Not right]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_propertyused_UnusedPropertiesOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:UnusedProperties");

			$this->assertFalse($this->isElementPresent("link=NoPageWillUseMe"));

	}

	public function test_propertynotused_UnusedPropertiesOnSpecialPage()
	{
		$this->type("searchInput", "UseProp");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->open($this->getUrl() ."index.php/Special:UnusedProperties");

			$this->assertTrue($this->isElementPresent("link=NoPageWillUseMe"));

	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Special:UnusedProperties");
		$this->click("link=NoPageWillUseMe");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
	}
}
