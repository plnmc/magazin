<?php
  abstract class Navigation
  {
    const PAGE = "page";    //name of http variable which keeps page name
    public static $pagesArray;
    protected $pathToScript;
    protected $pageObject;  // page object

    public function __construct($pathToScript)
    {
      $this->pathToScript = $pathToScript;
      $page = $_REQUEST[Navigation::PAGE];

      //if variable $page has wrong value - assign value of 0th key to it
      if (!array_key_exists($page, $this->pagesArray))
      {
        $pageKeys = array_keys($this->pagesArray);
        $page = $pageKeys[0];
      }
      //define type of class which represents the page
      $type = $this->pagesArray[$page]['type'];
      //create object of this class (inherited from class Page)
      $this->pageObject = new $type($pathToScript);
    }

    public function display()
    {
      $this->displayCommonPart();
      Display::debug(print_r($this->pageObject, true));
      $this->pageObject->display();
    }

    abstract protected function displayCommonPart();

  }
?>
