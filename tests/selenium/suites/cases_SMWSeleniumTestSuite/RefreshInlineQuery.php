<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class RefreshInlineQuery extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "SCFBestTeamInWorld");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=SCFBestTeamInWorld");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[BTIWProperty::SCF]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageForRefreshing");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPageForRefreshing");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "The best football team in world is {{#show: SCFBestTeamInWorld | ?BTIWProperty }}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/SCFBestTeamInWorld");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[BTIWProperty::SC Freiburg]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageForRefreshing");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		
		$this->click("link=Refresh");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isTextPresent("SC Freiburg"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php?title=TestPageForRefreshing&action=purge");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "SCFBestTeamInWorld");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
