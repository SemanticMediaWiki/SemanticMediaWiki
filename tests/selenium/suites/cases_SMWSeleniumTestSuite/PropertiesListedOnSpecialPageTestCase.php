<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class PropertiesListedOnSpecialPageTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "PropertyListTest1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=PropertyListTest1");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[AaaProperty::FirstUse]]\nTest page.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "PropertyListTest2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=PropertyListTest2");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[AaaProperty::SecondUse]]\nTest page.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:AabProperty");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:AabProperty");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This is a unused property.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_linkspresent_PropertiesListedOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");

			$this->assertTrue($this->isElementPresent("link=AaaProperty"));
		

			$this->assertTrue($this->isElementPresent("link=Page"));
		
	}

	public function test_numberofusage_PropertiesListedOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");

			$this->assertTrue($this->isTextPresent("AaaProperty of type Page (2)"));
		
	}

	public function test_textpresent_PropertiesListedOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");

			$this->assertTrue($this->isTextPresent("AaaProperty of type Page"));
		
	}

	public function test_tooltipicon_PropertiesListedOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");
		$this->click("//div[@id='bodyContent']/ol/li[1]/span[2]/img");
		$this->click("//div[9]");
	}

	public function test_unusedpropertynotpresent_PropertiesListedOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");

			$this->assertFalse($this->isTextPresent("AabProperty"));
		
	}

	public function testTeardown()
	{
		$this->type("searchInput", "Property:AabProperty");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "PropertyListTest1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "PropertyListTest2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main page");
		$this->waitForPageToLoad("30000");
	}
}
