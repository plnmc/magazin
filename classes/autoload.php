<?php
  function __autoload($className)
  {
    $dirs = array ("/", "/navigation/", "/navigation/objectpages/", "/navigation/wizardpages/",);
    if (!class_exists($className, false))
    {
      foreach ($dirs as $dir)
      {
        $classFileName = dirname(__FILE__).$dir.$className.'.php';
        if (file_exists($classFileName))
        {
          require_once($classFileName);
        }
      }
    }
  }

?>