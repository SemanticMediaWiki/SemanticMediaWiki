<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class FactboxShowsProperties extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "TestPerson James Orange");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPerson James Orange");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has dad::TestPerson Mike Orange]]\n[[Has mum::TestPerson Sandra Red-Orange]]\n[[Has half sister::TestPerson Olivia Red]]\n[[Has half sister::TestPerson Michelle Orange]]\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/TestPerson_James_Orange");

			$this->assertTrue($this->isTextPresent("Has dad"));
		

			$this->assertTrue($this->isTextPresent("TestPerson Mike Orange  +"));
		

			$this->assertTrue($this->isElementPresent("link=Has mum"));
		

			$this->assertTrue($this->isTextPresent("and"));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/TestPerson_James_Orange");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
