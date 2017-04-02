<?php
  class Shop
  {    const STATE_NOT_INSTALLED = 1;
    const STATE_READY = 2;
    const STATE_SUSPENDED = 3;

    private $id;
    private $state;
    private $shopName;
    private $email;
    private $pass;
    private $pathShop;
    private $adminDir;
    private $subDomain;
    private $contact;
    private $database;

    function __construct($shopName, $email, $pass, $subDomain, $contact, $database, $state = Shop::STATE_NOT_INSTALLED,
      $pathShop = null, $adminDir = null, $id = null)
    {
      $this->shopName = $shopName;
      $this->email = $email;
      $this->pass = $pass;
      $this->subDomain = $subDomain;
      $this->contact = $contact;
      $this->database = $database;
      $this->state = $state;
      $this->pathShop = $pathShop;
      if ($adminDir == null)
      {        $adminDir = "admin".rand(100, 999);      }
      else
      {
        $this->adminDir = $adminDir;
      }
      $this->id = $id;
    }

    function getId()
    {
      return $this->id;
    }

    function getState()
    {
      return $this->state;
    }

    function getShopName()
    {
      return $this->shopName;
    }

    function getEmail()
    {
      return $this->email;
    }

    function getPass()
    {
      return $this->pass;
    }

    function getPathShop()
    {
      return $this->pathShop;
    }

    function getAdminDir()
    {
      return $this->adminDir;
    }

    function getSubDomain()
    {
      return $this->subDomain;
    }

    function getContact()
    {
      return $this->contact;
    }

    function getDatabase()
    {
      return $this->database;
    }

    function setId($id)
    {
      $this->id = $id;
    }

    function setState($state)
    {
      $this->state = $state;
    }

    function setShopName($shopName)
    {
      $this->shopName = $shopName;
    }

    function setEmail($email)
    {
      $this->email = $email;
    }

    function setPass($pass)
    {
      $this->pass = $pass;
    }

    function setPathShop($pathShop)
    {
      $this->pathShop = $pathShop;
    }

    function setAdminDir($adminDir)
    {
      $this->adminDir = $adminDir;
    }

    function create()
    {
      $this->subDomain->create();
      $this->database->create();
      $this->copyShopDistro();
      $this->runShopInstaller();
    }

    private function copyShopDistro()
    {
      //copying files to subdomain vhost dir
    }

    private function runShopInstaller()
    {
      //get DB and dbuser and db password and other info
      //run installer
      //delete folder 'install'
      //rename folder 'admin'
      //send success letter
    }

  }
  }
?>
