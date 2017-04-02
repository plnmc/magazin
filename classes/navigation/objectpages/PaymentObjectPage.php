<?php
  class PaymentObjectPage extends ObjectPage
  {

    protected $listQuery = "select * from payments";



    public function __construct()
    {    }

    public function createObj()
    {      $page = $_REQUEST["page"];
      $paymentAmount = $_REQUEST["payment_amount"];
      $paymentTypeId = $_REQUEST["payment_type_id"];
      $dogovorTypeId = $_REQUEST["dogovor_type_id"];
      $managerId = $_REQUEST["manager_id"];
      $shopId = $_REQUEST["shop_id"];
      $comment = $_REQUEST["comment"];
      $save = $_REQUEST["save"];
      if ($save == 1)
      {
        $dbConnection = new DbSafeConnection();
        $query = "insert into payments (shop_id, payment_date, payment_amount, payment_type_id, dogovor_type_id, manager_id, comment, created_date) values ('%s', NOW(), '%s', '%s', '%s', '%s', '%s', NOW())";
        if ($dbConnection->safeExecute(sprintf($query,
          mysql_real_escape_string($shopId), mysql_real_escape_string($paymentAmount), mysql_real_escape_string($paymentTypeId), mysql_real_escape_string($dogovorTypeId), mysql_real_escape_string($managerId), mysql_real_escape_string($comment))))
        {
          header ("Location: index.php?page=$page&operation=list");
        }
        else
        {
          Display::append("Ошибка при добавлении платежа");
          exit();
        }
      }
      $dbConnection = new DbSafeConnection();
      Display::append("<form action=\"index.php?page=$page&operation=create\" method=post>");

      Display::append("Клиент: ");
      Display::append("<select name=shop_id>");
      $query = "select shops.shop_id as shop_id, domain_names.domain_name as domain_name from shops, domain_names where shops.shop_id = domain_names.shop_id and domain_names.domain_type_id = 1 order by domain_names.domain_name";
      if ($dbConnection->safeExecute($query, $dbResult))
      {
        foreach ($dbResult as $row)
        {          $str = "<option value=\"".$row['shop_id']."\">".$row['domain_name']."</option>";
          Display::append($str);
        }
      }
      Display::append("</select><br>");

      Display::append("Платежный метод: ");
      Display::append("<select name=\"payment_type_id\">");
      Display::append("<option disabled>Выберите платежный метод</option>");
      $query = "select payment_type_id, requisites from payment_types where payment_type_state=1 order by payment_type_id asc";
      if ($dbConnection->safeExecute($query, $dbResult))
      {
        foreach ($dbResult as $row)
        {
          $requisites = $row['requisites'];
          $requisites = str_replace("\n", "", $requisites);
          $requisites = str_replace("\r", "", $requisites);
          $length = strlen($requisites);
          if ($length > 50)
          {
            $requisites = substr($requisites, 0, 50) . "...";
          }
          Display::append("<option value=\"".$row['payment_type_id']."\">$requisites</option>");
        }      }
      Display::append("</select><br>");

      Display::append("Сумма платежа: ");
      Display::append("<input type=text name=payment_amount><br>");

      Display::append("Тип договора: ");
      Display::append("<select name=dogovor_type_id>");
      $query = "select dogovor_type_id, dogovor_type_name from dogovor_types order by dogovor_type_id";
      if ($dbConnection->safeExecute($query, $dbResult))
      {
        foreach ($dbResult as $row)
        {
          $str = "<option value=\"".$row['dogovor_type_id']."\">".$row['dogovor_type_name']."</option>";
          Display::append($str);
        }
      }
      Display::append("</select><br>");


      Display::append("Менеджер: ");
      Display::append("<select name=manager_id>");
      $query = "select manager_id, surname, name from managers order by manager_id";
      if ($dbConnection->safeExecute($query, $dbResult))
      {
        foreach ($dbResult as $row)
        {
          $str = "<option value=\"".$row['manager_id']."\">".$row['surname']." ".$row['name']."</option>";
          Display::append($str);
        }
      }
      Display::append("</select><br>");


      Display::append("Комментарий:<br>");
      Display::append("<textarea cols=30 rows=5 name=comment>");
      Display::append($comment);
      Display::append("</textarea>");
      Display::append("<input type=hidden name=save value=1>");
      Display::append("<br><input type=submit value='Сохранить'>");    }

    public function readObj()
    {    }

    public function updateObj()
    {      $page = $_REQUEST["page"];
      $paymentTypeId = $_REQUEST["payment_type_id"];
      $requisites = $_REQUEST["requisites"];
      $paymentTypeState = $_REQUEST["payment_type_state"];
      $save = $_REQUEST["save"];
      $ok = $_REQUEST["ok"];
      if ($save == 1)
      {
        if ($paymentTypeState != 1) $paymentTypeState = 0;        $query = "update payment_types set requisites='%s', payment_type_state='%s' where payment_type_id = '%s'";
        $dbConnection = new DbSafeConnection();
        if ($dbConnection->safeExecute(sprintf($query, $requisites, $paymentTypeState, $paymentTypeId)))
        {
          header ("Location: index.php?page=$page&operation=update&payment_type_id=$paymentTypeId&ok=1");
        }
        else
        {
          Display::append("Error updating payment type");
          exit();
        }
      }
      if ($ok == 1)
      {
        Display::append("Изменения сохранены!<br>");
      }
      Display::append("ID $paymentTypeId <br>");
      $query = "select * from payment_types where payment_type_id = '%s'";
      $dbConnection = new DbSafeConnection();
      $dbConnection->safeExecute(sprintf($query, $paymentTypeId), $paymentTypeInfo);
      $paymentTypeInfo = $paymentTypeInfo[0];
      Display::append("<form action=\"index.php?page=$page&operation=update&payment_type_id=$paymentTypeId\" method=post>");

      Display::append("Тип оплаты включен: <input type=checkbox name=payment_type_state value=1");
      if ($paymentTypeInfo["payment_type_state"] == 1)
      {
        Display::append(" checked>");
      }
      else
      {        Display::append(">");
      }
      Display::append("<br>Реквизиты:<br><textarea cols=30 rows=5 name=requisites>");
      Display::append($paymentTypeInfo["requisites"]);
      Display::append("</textarea>");      Display::append("<input type=hidden name=save value=1>");
      Display::append("<br><input type=submit value='Сохранить'>");
    }

    public function deleteObj()
    {    }

    public function listObj()
    {
      $page = $_REQUEST["page"];
      Display::append("<a href=\"index.php?page=$page&operation=create\">Добавить платеж</a><br><br>");
      $header = "<table border=1 cellspacing=0>";
      $query = "select payments.payment_id as payment_id, domain_names.domain_name as domain_name, "
        ."payments.payment_amount as payment_amount, payments.payment_date as payment_date, payment_types.requisites as requisites, dogovor_types.dogovor_type_name as dogovor_type_name, managers.surname as manager_surname, managers.name as manager_name, payments.comment as comment "
        ."from payments, payment_types, dogovor_types, managers, domain_names where payments.dogovor_type_id = dogovor_types.dogovor_type_id and payments.manager_id = managers.manager_id and payments.payment_type_id = payment_types.payment_type_id and payments.shop_id = domain_names.shop_id and domain_names.domain_type_id = 1 order by payments.payment_id";
      $dbResult = null;
      $dbConnection = new DbSafeConnection();
      $dbConnection->safeExecute($query, $dbResult, false);
      if ($dbResult == null)
      {
        Display::append("Result is empty!");
      }
      else
      {        Display::append($header);
        Display::append("<tr><td>ID<td>Клиент<td>Сумма<td>Дата<td>Тип платежа<td>Тип договора<td>Менеджер<td>Комментарий");
        foreach ($dbResult as $row)
        {

          Display::append("<tr>");
          Display::append("<td>".$row["payment_id"]);
          Display::append("<td>".$row["domain_name"]);
          Display::append("<td>".$row["payment_amount"]);
          Display::append("<td>".$row["payment_date"]);
          Display::append("<td>".$row["requisites"]);
          Display::append("<td>".$row["dogovor_type_name"]);
          Display::append("<td>".$row["manager_surname"]." ".$row["manager_name"]);
          Display::append("<td>".nl2br($row["comment"]));
        }
        Display::append("</table>");
      }

    }

  }
?>
