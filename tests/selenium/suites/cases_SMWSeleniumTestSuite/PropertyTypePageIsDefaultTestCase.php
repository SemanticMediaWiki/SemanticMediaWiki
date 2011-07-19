<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class PropertyTypePageIsDefaultTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:AaaDefaultType");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:AaaDefaultType");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "Test Property.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "TestDefaultType");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=TestDefaultType");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[AaaDefaultType::CheckLink]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_checkpropertylist_PropertyTypePageIsDefault()
	{
		$this->open($this->getUrl() ."index.php/TestDefaultType");
		$this->type("searchInput", "Special:Properties");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");

			$this->assertTrue($this->isTextPresent("AaaDefaultType of type Page"));
		
	}

	public function test_verifylink_PropertyTypePageIsDefault()
	{
		$this->open($this->getUrl() ."index.php/TestDefaultType");

			$this->assertTrue($this->isElementPresent("link=CheckLink"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Special:Properties");
		$this->type("searchInput", "TestDefaultType");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:AaaDefaultType");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
	}
}
