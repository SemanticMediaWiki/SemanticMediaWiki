<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class AnnotatePageWithProperty extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "TestPerson Judith Silver");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPerson Judith Silver");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. __SHOWFACTBOX__");
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
		$this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. __SHOWFACTBOX__\n[[Favored drink::Martini]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Favored drink"));


			$this->assertTrue($this->isElementPresent("//div[@id='mw-data-after-content']/div/table/tbody/tr/td[2]/a"));

		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Martini"));

	}

	public function testTest02() {
		$this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertFalse($this->isTextPresent("pizza"));


			$this->assertFalse($this->isTextPresent("Funghi"));


			$this->assertFalse($this->isTextPresent("pasta"));


			$this->assertFalse($this->isTextPresent("Aglio e Oglio"));

		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Favored pasta"));


			$this->assertTrue($this->isElementPresent("link=Favored pizza"));


			$this->assertTrue($this->isElementPresent("link=Aglio e Oglio"));


			$this->assertTrue($this->isElementPresent("link=Funghi"));

	}

	public function testTest03() {
		$this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n__SHOWFACTBOX__\n[[Is child of::Margareth Silver|Maggie Silver]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Maggie Silver"));


			$this->assertTrue($this->isElementPresent("link=Margareth Silver"));

		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n[[Is child of::Margareth Silver|Maggie Silver]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Maggie Silver"));


			$this->assertFalse($this->isElementPresent("link=Margareth Silver"));

	}

	public function testTest04() {
	 $this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
	 $this->click("link=Edit");
	 $this->waitForPageToLoad("10000");
	 $this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n[[Is child of::Margareth Silver|Maggie Silver]]\n[[Is working for::Drives car from::Toyota]]");
	 $this->click("wpSave");
	 $this->waitForPageToLoad("10000");
	 try {
	 	$this->assertTrue($this->isElementPresent("link=Toyota"));
	 } catch (PHPUnit_Framework_AssertionFailedError $e) {
	 	//array_push($this->verificationErrors, $e->toString());
	 }
	 try {
	 	$this->assertFalse($this->isElementPresent("link=Drives car from"));
	 } catch (PHPUnit_Framework_AssertionFailedError $e) {
	 	//array_push($this->verificationErrors, $e->toString());
	 }
	 try {
	 	$this->assertFalse($this->isElementPresent("link=Is working for"));
	 } catch (PHPUnit_Framework_AssertionFailedError $e) {
	 	//array_push($this->verificationErrors, $e->toString());
	 }
	 $this->click("link=Edit");
	 $this->waitForPageToLoad("10000");
	 $this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n[[Is child of::Margareth Silver|Maggie Silver]]\n[[Is working for::Drives car from::Toyota]]\n__SHOWFACTBOX__");
	 $this->click("wpSave");
	 $this->waitForPageToLoad("10000");
	 try {
	 	$this->assertTrue($this->isElementPresent("link=Drives car from"));
	 } catch (PHPUnit_Framework_AssertionFailedError $e) {
	 	//array_push($this->verificationErrors, $e->toString());
	 }
	 try {
	 	$this->assertTrue($this->isElementPresent("link=Is working for"));
	 } catch (PHPUnit_Framework_AssertionFailedError $e) {
	 	//array_push($this->verificationErrors, $e->toString());
	 }
	}

	public function testTest05() {
		$this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "Some text for Judith. \n[[Favored drink::Martini]]\n{{#set:Favored pizza=Funghi|Favored pasta=Aglio e Oglio}}\n[[Is child of::Margareth Silver|Maggie Silver]]\n[[Is working for::Drives car from::Toyota]]\n__SHOWFACTBOX__\n[[Lives together with::Jeremy Green-White]] [[Lives together with::Julia Pink-Red]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=Lives together with"));


			$this->assertTrue($this->isElementPresent("//div[@id='mw-data-after-content']/div/table/tbody/tr[7]/td[2]/a[1]"));


			$this->assertTrue($this->isElementPresent("//div[@id='mw-data-after-content']/div/table/tbody/tr[7]/td[2]/a[2]"));


			$this->assertTrue($this->isTextPresent("and"));

	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/TestPerson_Judith_Silver");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
