<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class RefreshLinkForEveryPage extends SeleniumTestCase {


	public function testSetup()
	{
		;
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->click("link=Random page");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Refresh"));
		
		$this->click("link=Random page");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Refresh"));
		
		$this->click("link=Random page");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Refresh"));
		
	}

	public function testTeardown()
	{
		;
	}
}
