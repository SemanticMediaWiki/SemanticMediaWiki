<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class ShowFactboxTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Factboxtest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Factboxtest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Factbox::is shown]]\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Factboxtest");

			$this->assertTrue($this->isTextPresent("Facts about"));

	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Factboxtest");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("//div[@id='p-logo']/a");
		$this->waitForPageToLoad("30000");
	}
}
