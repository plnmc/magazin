<?php
  class Contact
  {
    private $name;
    private $surname;
    private $email;

    function __construct($name, $surname, $email)
    {
      $this->name = $name;
      $this->surname = $surname;
      $this->email = $email;
    }

    function getName()
    {
      return $this->name;
    }

    function getSurname()
    {
      return $this->surname;
    }

    function getEmail()
    {
      return $this->email;
    }
  }
?>