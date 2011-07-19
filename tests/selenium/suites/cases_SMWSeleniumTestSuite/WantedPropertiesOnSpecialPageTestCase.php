<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class WantedPropertiesOnSpecialPageTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "WantedPropertyTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=WantedPropertyTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[RedLinkProperty::Is used]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "WantedPropertyTest2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=WantedPropertyTest2");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[RedLinkProperty::Is used]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_numberofuses_WantedPropertiesOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:WantedProperties");

			$this->assertTrue($this->isTextPresent("RedLinkProperty (2 uses)"));
		
	}

	public function test_showsproperty_WantedPropertiesOnSpecialPage()
	{
		$this->open($this->getUrl() ."index.php/Special:WantedProperties");

			$this->assertTrue($this->isElementPresent("link=RedLinkProperty"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/WantedPropertyTest");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "WantedPropertyTest2");
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
