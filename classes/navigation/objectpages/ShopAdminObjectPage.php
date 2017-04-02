<?php
  class ShopAdminObjectPage extends ObjectPage
  {

    protected $listQuery = "select shops.shop_id, domain_names.domain_name, shops.email, UNIX_TIMESTAMP(shops.created_date) as created_date, UNIX_TIMESTAMP(shops.next_bill_date) as next_bill_date, shops.path_admin, shops.db_id, shops.shop_state_id, shops.shop_alive, shops.show_in_clients, shop_types.shop_type_name as shop_type_name from shops, domain_names, shop_types where shops.shop_id = domain_names.shop_id and shops.shop_type_id = shop_types.shop_type_id";
    protected $pageHeader = "Все магазины";
    const ITEMS_PER_PAGE = 500;


    public function __construct()
    {    }

    public function display()
    {
      $operation = $_REQUEST["operation"];
      switch ($operation)
      {        case "set_shop_type":
          $this->setShopType();
          break;
        case "toggle_state":
          $this->toggleState();
          break;        case "toggle_alive":
          $this->toggleAlive();
          break;
        case "toggle_show":
          $this->toggleShow();
          break;
        case "system_login":
          $this->systemLogin();
          break;
      }      parent::display();    }

    public function createObj()
    {    }

    public function readObj()
    {      $page = $_REQUEST["page"];
      $shopId = $_REQUEST["shop_id"];

      $header = "<table border=1 cellspacing=0 width=98%>"
        ."<col style=\"width: 10em; text-align:right\"/><col style=\"text-align:left\"/>";
      $header2 = "<table border=1 cellspacing=0 width=98%>";

      $query = "select shops.shop_id, shops.email, UNIX_TIMESTAMP(shops.created_date) as created_date, UNIX_TIMESTAMP(shops.next_bill_date) as next_bill_date, shops.password, shops.path_admin, shops.db_id, shops.shop_state_id, shops.shop_alive from shops where shops.shop_id = $shopId";
      $dbConnection = new DbSafeConnection();
      $dbConnection->safeExecute($query, $shopInfoArray, false);
      $shopInfo = $shopInfoArray[0];

      Display::append("<b>Shop</b><br>".$header);
      Display::append("<tr><td align=right>Shop ID:<td>".$shopInfo["shop_id"]);

      $mainDomainName = $dbConnection->getMainDomainNameByShopId($shopId);
      Display::append("<tr><td align=right>Domain name:<td><a target=_blank href=\"http://$mainDomainName\">$mainDomainName</a>");

      $query = "select domain_names.domain_name from shops, domain_names where shops.shop_id = domain_names.shop_id and domain_names.domain_type_id = 2 and shops.shop_id = $shopId";
      $dbConnection->safeExecute($query, $domainInfoArray, false);
      if ($domainInfoArray != null)
      {
        foreach ($domainInfoArray as $domainInfo)
        {
          Display::append("<tr><td align=right>Alias:<td><a target=_blank href=\"http://".$domainInfo["domain_name"]."\">".$domainInfo["domain_name"]."</a>");
        }
      }
      $loginLink = "$mainDomainName/".$shopInfo["path_admin"];
      Display::append("<tr><td align=right>Login link to admin panel:<td><a target=_blank href=\"http://$loginLink\">$loginLink</a>");
      Display::append("<tr><td align=right>Reset system password and login:<td><a target=_blank href=\"index.php?page=$page&operation=system_login&loginlink=$loginLink&shop_id=".$shopInfo["shop_id"]."\">$loginLink</a>");
      Display::append("<tr><td align=right>E-mail:<td>".$shopInfo["email"]);
      Display::append("<tr><td align=right>Password:<td>".$shopInfo["password"]);
      Display::append("<tr><td align=right>Created:<td>".date("d.m.Y", $shopInfo["created_date"]));
      Display::append("<tr><td align=right>Next bill:<td>");

      Display::append("<table border=0>
      <tr>
        <td rowspan=2 valign=center>".date("d.m.Y", $shopInfo["next_bill_date"])."
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=add&period=day\">+day</a>
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=add&period=month\">+month</a>
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=add&period=year\">+year</a>
      <tr>
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=sub&period=day\">-day</a>
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=sub&period=month\">-month</a>
        <td><a href=\"billdate.php?shop_id=".$shopInfo["shop_id"]."&operation=sub&period=year\">-year</a>
      </table>");

      $state = DataProcessor::OnOff($shopInfo["shop_state_id"]);
      $alive = DataProcessor::OnOff(1-$shopInfo["shop_alive"]);
      $dbConnectionShop = $dbConnection->getShopDbConnection($shopId);
      Display::append("<tr><td align=right>Количество товаров:<td>".$dbConnectionShop->getProductsNumber());
      Display::append("<tr><td align=right>Включен:<td><a href=\"index.php?page=$page&operation=toggle_state&shop_id=".$shopInfo["shop_id"]."\">$state</a> (notification email will NOT be sent)");
      if ($state == "OFF")
      {        Display::append("<tr><td align=right>Заархивирован:<td><a href=\"index.php?page=$page&operation=toggle_alive&shop_id=".$shopInfo["shop_id"]."\">$alive</a>");
      }
      Display::append("<tr><td align=right>Import data from CSV:<td><a href=\"import.php?db_id=".$shopInfo["db_id"]."\">import</a>");
      Display::append("<tr><td align=right>Shop type:");
      Display::append("<form action=\"?shop_id=$shopId&operation=set_shop_type\" method=post><td><select name=shop_type>");

      $currentShopType = $dbConnection->getShopType($shopId);

      $query = "select shop_type_id, shop_type_name from shop_types";
      $dbConnection->safeExecute($query, $shopTypes);
      foreach ($shopTypes as $shopType)
      {
        $selected = ($currentShopType == $shopType["shop_type_id"])?"selected ":"";        Display::append("<option ".$selected."value=".$shopType["shop_type_id"].">".$shopType["shop_type_name"]."</option>");      }
      Display::append("</select><input type=submit value=OK></form>");


      $query = "select dbs.db_id, dbs.name, dbs.host, dbs.user, dbs.password from dbs, shops where dbs.db_id = shops.db_id and shops.shop_id = $shopId";
      $dbConnection->safeExecute($query, $dbInfoArray);
      $dbInfo = $dbInfoArray[0];

      Display::append("</table><br><b>Database</b><br>\n$header");
      Display::append("<tr><td align=right>Database ID:<td>".$dbInfo["db_id"]);
      Display::append("<tr><td align=right>Database host:<td>".$dbInfo["host"]);
      Display::append("<tr><td align=right>Database name:<td>".$dbInfo["name"]);
      Display::append("<tr><td align=right>Database user:<td>".$dbInfo["user"]);
      Display::append("<tr><td align=right>Database pass:<td>".$dbInfo["password"]);
      Display::append("</table>");
      Display::append("<br><b>Contacts</b><br>");

      $query = "select contacts.contact_id, contacts.surname, contacts.name, contacts.fathername, contacts.email, contacts.organization, contacts.position, contacts.phones, contacts.info from contacts, shop_contacts where shop_contacts.shop_id = $shopId and contacts.contact_id = shop_contacts.contact_id";
      Display::executeQueryAndDisplayResult($dbConnection, $query, false);
    }

    public function updateObj()
    {    }

    public function deleteObj()
    {    }

    public function listObj()
    {
      $page = $_REQUEST["page"];
      $displayPage = $_REQUEST["display_page"]; //1, 2...
      if (!$displayPage) $displayPage = 1;

      $sortColumn = $_REQUEST["sort_column"];
      if (!$sortColumn) $sortColumn = 1;

      $sortOrder =  $_REQUEST["sort_order"];
      if (!$sortOrder) $sortOrder = "asc";
      $start = ($displayPage-1) * self::ITEMS_PER_PAGE;
      $count = self::ITEMS_PER_PAGE;
      $header = "<table border=1 cellspacing=0>";
   	  $query = $this->listQuery;

  	  //search form begin
      if ($page != "oldshops")
      {
    	  if (isset($_REQUEST['filterbyname']))
        {
          $filterbyname = $_REQUEST['filterbyname'];
      		if (preg_match('/^[a-z0-9.-]{2,32}$/ui', $filterbyname))
          {
      			$wherecondition = " and (domain_name like '%$filterbyname%' or shops.email like '%$filterbyname%')";
      		} else
          {
      			$wherecondition = '';
      		}
    	  }
    	  Display::append("<p style='font-size:9pt;'>Добавь в урл параметр '&filterbyname=' и будет лайк\поиск по имени  или майлу магазина.</p>");
    	  Display::append("<form method=get> Фильтр по имени:
<input type=hidden name='page' value='$page'>
<input type=text name='filterbyname' value='$filterbyname'>
<input type='submit' value='Отправить'> </form>");
    	  $query = $this->listQuery." $wherecondition order by $sortColumn $sortOrder limit $start, $count";
      }
  	  //search form end

      $dbResult = null;
      $dbConnection = new DbSafeConnection();
      $dbConnection->safeExecute($query, $dbResult, false);
      if ($dbResult == null)
      {
        Display::append("Result is empty!");
      }
      else
      {
        // pages
        $dbConnection->safeExecute($this->listQuery, $dbResult1, false);
        $numRows = count($dbResult1);
        Display::append($this->pageHeader."<br>Всего элементов в списке: ".$numRows);
        $numPages = ceil ($numRows / self::ITEMS_PER_PAGE);
        if ($numPages > 1)
        {
          Display::append("<br>Отображается ".self::ITEMS_PER_PAGE." на странице. Страница: ");
          for ($i=0; $i<$numPages; $i++)
          {            if ($displayPage == $i+1)
            {
              Display::append( "<b>".($i+1)."</b>&nbsp;&nbsp;" );
            }
            else
            {
              Display::append( "<a href=\"index.php?page=$page&display_page=" .($i+1). "&sort_order=$sortOrder&sort_column=$sortColumn\">" .($i+1). "</a>&nbsp;&nbsp;" );
            }
          }
        }

        // page
        Display::append($header);
        Display::append("<tr><td>".$this->sortLink("ID",1)
          ."<td>".$this->sortLink("Subdomain",2)
          ."<td>".$this->sortLink("Email",3)
          ."<td>".$this->sortLink("Created",4)
          ."<td>".$this->sortLink("Next bill",5)
          ."<td>".$this->sortLink("State",8)
          ."<td>".$this->sortLink("Archived",9)
          ."<td>".$this->sortLink("Show in clients",10)
          ."<td>".$this->sortLink("Shop type",11));
        foreach ($dbResult as $row)
        {
          //1 - ON, 0-OFF
          $state = DataProcessor::OnOff($row["shop_state_id"]);
          $alive = DataProcessor::OnOff(1-$row["shop_alive"]);
          $showInClients = DataProcessor::OnOff($row["show_in_clients"]);
          Display::append("<tr>");
          Display::append("<td><a href=\"index.php?page=$page&operation=read&shop_id=".$row["shop_id"]."\">".$row["shop_id"]."</a>");
          Display::append("<td><a target=_blank href=\"http://".$row["domain_name"]."\">".$row["domain_name"]."</a>");
          Display::append("<td>".$row["email"]);
          Display::append("<td>".date("d.m.Y", $row["created_date"]));
          //Если магазин включен и осталось меньше Config::TIME_BUFFER_DAYS дней до даты платежа - выделять цветом
          if ((time() + Config::TIME_BUFFER_DAYS*24*3600 > $row["next_bill_date"]) && ($state == "ON"))
          {
            $markup = "<b style='color:red'>";
            $endmarkup = "</b>";
          }
          else
          {
            $markup = "";
            $endmarkup = "";
          }
          Display::append("<td>$markup".date("d.m.Y", $row["next_bill_date"]).$endmarkup);
          Display::append("<td><a href=\"index.php?page=$page&operation=toggle_state&notify=1&display_page=$displayPage&sort_order=$sortOrder&sort_column=$sortColumn&shop_id=".$row["shop_id"]."\">$state</a>");
          Display::append("<td>".$alive);
          Display::append("<td><a href=\"index.php?page=$page&operation=toggle_show&display_page=$displayPage&sort_order=$sortOrder&sort_column=$sortColumn&shop_id=".$row["shop_id"]."\">$showInClients</a>");
          Display::append("<td>".$row["shop_type_name"]);
        }
        Display::append("</table>");

        if ($page == "oldshops")
        {
          Display::append("<textarea cols=100 rows=500>");

          $dbConnection->safeExecute($this->domainNamesQuery, $dbResult, false);
          $numRows = count($dbResult);
          Display::append($this->pageHeader."Всего доменов: ".$numRows."\n\n");
          foreach ($dbResult as $row)
          {            Display::append("./subdomain -r -subdomains ".str_replace(".vsemagazin.ru", "", $row["domain_name"])." -domain vsemagazin.ru\n" );          }
          foreach ($dbResult as $row)
          {
            Display::append("rm -f /sandbox/backups/".str_replace(".vsemagazin.ru", "", $row["domain_name"])."___*\n" );
          }

          $dbConnection->safeExecute($this->dbNamesQuery, $dbResult, false);
          $numRows = count($dbResult);
          Display::append($this->pageHeader."Всего баз данных: ".$numRows."\n\n");
          foreach ($dbResult as $row)
          {
            Display::append("./database -r ".$row["db_name"]."\n" );
          }

          $dbConnection->safeExecute($this->shopIdsQuery, $dbResult, false);
          $numRows = count($dbResult);
          Display::append($this->pageHeader."Всего магазинов: ".$numRows."\n\n");
          $shopIdsToRemove = "";
          foreach ($dbResult as $row)
          {
            $shopIdsToRemove .= $row["shop_id"].",";
          }
          $shopIdsToRemove = substr($shopIdsToRemove, 0, -1);
          Display::append("\nbegin;");
          Display::append("\ndelete from dbs where db_id in (select db_id from shops where shop_id in (".$shopIdsToRemove."));");
          Display::append("\ndelete from domain_names where shop_id in (".$shopIdsToRemove.");");
          Display::append("\ndelete from shop_contacts where shop_id in (".$shopIdsToRemove.");");
          Display::append("\ndelete from shops where shop_id in (".$shopIdsToRemove.");");

          Display::append("</textarea>");
        }
      }
    }

    public function setShopType()
    {
      $shopId = $_REQUEST['shop_id'];
      $shopType = $_REQUEST["shop_type"];
      $dbConnection = new DbSafeConnection();
      $result = $dbConnection->setShopType($shopId, $shopType);
      header("Location: index.php?page=shopadmin&operation=read&shop_id=".$shopId);
    }

    public function toggleShow()
    {
      $shopId = $_REQUEST['shop_id'];
      $displayPage = $_REQUEST["display_page"]; //1, 2...
      if (!$displayPage) $displayPage = 1;

      $sortColumn = $_REQUEST["sort_column"];
      if (!$sortColumn) $sortColumn = 1;

      $sortOrder =  $_REQUEST["sort_order"];
      if (!$sortOrder) $sortOrder = "asc";

      $dbConnection = new DbSafeConnection();
      $result = $dbConnection->toggleShowInClients($shopId);
      header("Location: index.php?page=shopadmin&display_page=$displayPage&sort_order=$sortOrder&sort_column=$sortColumn");
    }

    public function toggleState()
    {
      $shopId = $_REQUEST['shop_id'];
      $notify = $_REQUEST['notify'];
      $displayPage = $_REQUEST["display_page"]; //1, 2...
      if (!$displayPage) $displayPage = 1;

      $sortColumn = $_REQUEST["sort_column"];
      if (!$sortColumn) $sortColumn = 1;

      $sortOrder =  $_REQUEST["sort_order"];
      if (!$sortOrder) $sortOrder = "asc";

      $dbConnection = new DbSafeConnection();
      $state = $dbConnection->getShopState($shopId);
      $requiredState = 1 - $state;
      if (SystemOperations::setShopState($shopId, $requiredState))
      {
        if ($notify == 1)  //send mail
        {
          $dbConnection = new DbSafeConnection();
          $email = $dbConnection->getStaffEmailByShopId($shopId);
          $mainDomainName = $dbConnection->getMainDomainNameByShopId($shopId);
          $subject = sprintf(Strings::EMAIL_SUBJECT, $mainDomainName);
          if ($requiredState == 0)
          {
            $body = sprintf(nl2br(Strings::EMAIL_DISABLE), $mainDomainName);
          } else
          {
            $body = sprintf(nl2br(Strings::EMAIL_ENABLE), $mainDomainName);
          }
          DataProcessor::sendMail($email, $subject, $body);
          header("Location: index.php?page=shopadmin&display_page=$displayPage&sort_order=$sortOrder&sort_column=$sortColumn");
        }
        else  // do not send mail
        {
          header("Location: index.php?page=shopadmin&operation=read&shop_id=".$shopId);
        }
      }
      else
      {
        Display::append(sprintf(Strings::ERROR_SET_SHOP_STATE, $shopId));
        Display::show();
      }
    }

    public function toggleAlive()
    {
      $shopId = $_REQUEST['shop_id'];
      $displayPage = $_REQUEST["display_page"]; //1, 2...
      if (!$displayPage) $displayPage = 1;

      $sortColumn = $_REQUEST["sort_column"];
      if (!$sortColumn) $sortColumn = 1;

      $sortOrder =  $_REQUEST["sort_order"];
      if (!$sortOrder) $sortOrder = "asc";


      $dbConnection = new DbSafeConnection();
      $state = $dbConnection->getShopAliveState($shopId);
      $requiredState = 1 - $state;
      if (SystemOperations::setShopAliveState($shopId, $requiredState))
      {
        header("Location: index.php?page=shopadmin&operation=read&shop_id=".$shopId);
      }
      else
      {
        Display::appendError(sprintf(Strings::ERROR_SET_SHOP_STATE, $shopId));
        Display::show();
        exit();
      }

    }

    public function systemLogin()
    {
      $shopId = $_REQUEST['shop_id'];
      $loginLink = "http://".$_REQUEST['loginlink'];
      $password = SystemOperations::getNewSystemPassword($shopId);
      header("Location: ".$loginLink."/login.php?Submit=submit&email=".Config::SYSTEM_LOGIN."&passwd=$password");
    }
  }
?>
