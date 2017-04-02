<?php
  class AdminNavigation extends Navigation
  {
    public function __construct($pathToScript)
    {
    // name_of_GET_variable  => (page name, class name)
      $this->pagesArray = array (
        "shopadmin"=> array ("linkName"=>"Shop&nbsp;administration", "type"=>"ShopAdminObjectPage"),
        "oldshops"=> array ("linkName"=>"Old&nbsp;shops", "type"=>"OldShopsObjectPage"),
        "payments"=> array ("linkName"=>"Payments", "type"=>"PaymentObjectPage"),
        "payment_types"=> array ("linkName"=>"Payment types", "type"=>"PaymentTypeObjectPage"),
        "dbs"=> array ("linkName"=>"Databases", "type"=>"DatabaseObjectPage"),
        "shops"=> array ("linkName"=>"Shops", "type"=>"ShopObjectPage"),
        "contacts"=> array ("linkName"=>"Contacts", "type"=>"ContactObjectPage"),
        "domains"=> array ("linkName"=>"Domains", "type"=>"DomainObjectPage"),
        "domain_requests"=> array ("linkName"=>"DomainRequests", "type"=>"DomainRequestObjectPage")
        );
      parent::__construct($pathToScript);
    }

    protected function displayCommonPart()
    {
      Display::append("<table border=0 cellspacing=10 width=100%>");
      Display::append("<tr><td valign=top>");
      foreach ($this->pagesArray as $pageKey => $pageValue)
      {
        Display::append("<a href='".$this->pathToScript."?".Navigation::PAGE."=$pageKey'>".$pageValue['linkName']."</a><br>");
      }
      Display::append("<td valign=top width=100%>");
    }

  }
?>
