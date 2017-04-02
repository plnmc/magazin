<?php
  abstract class Page
  {
    protected $pathToScript;         //path to a script

    function __construct($pathToScript)
    {
      $this->pathToScript = $pathToScript;
    }

    abstract public function display();

  }
?>
