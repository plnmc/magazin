<?php
  class ShopObjectPage extends ObjectPage
  {

    protected $listQuery = "select * from shops order by productscounter desc";



    public function __construct()
    {    }

    public function createObj()
    {    }

    public function readObj()
    {    }

    public function updateObj()
    {    }

    public function deleteObj()
    {    }

  }
?>
