<?php
  abstract class WizardPage extends Page
  {    abstract public function validate();
    abstract public function prevPage();
    abstract public function nextPage();

    protected function redirectToPage($page)
    {
      header ("Location: ".$this->pathToScript.$this->urlSymbol().Navigation::PAGE."=".$page);
    }

    protected function urlSymbol()
    {
      if (strpos($this->pathToScript, "?") !== false)
      {
        return "&";
      }
      return "?";
    }
  }
?>
