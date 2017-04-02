<?php
  class PaymentTypeObjectPage extends ObjectPage
  {

    protected $listQuery = "select * from payment_types";



    public function __construct()
    {    }

    public function createObj()
    {      $page = $_REQUEST["page"];
      $requisites = $_REQUEST["requisites"];
      $save = $_REQUEST["save"];
      if ($save == 1)
      {
        $query = "insert into payment_types values (NULL, '%s', 1, NOW(), '')";
        $dbConnection = new DbSafeConnection();
        if ($dbConnection->safeExecute(sprintf($query, mysql_real_escape_string($requisites))))
        {
          header ("Location: index.php?page=$page&operation=list");
        }
        else
        {
          Display::append("Error creating payment type");
          exit();
        }
      }
      Display::append("Введите реквизиты:<br>");
      Display::append("<form action=\"index.php?page=$page&operation=create\" method=post>");
      Display::append("<textarea cols=30 rows=5 name=requisites>");
      Display::append($requisites);
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
        if ($dbConnection->safeExecute(sprintf($query, mysql_real_escape_string($requisites), mysql_real_escape_string($paymentTypeState), mysql_real_escape_string($paymentTypeId))))
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
      $dbConnection->safeExecute(sprintf($query, mysql_real_escape_string($paymentTypeId)), $paymentTypeInfo);
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
      Display::append("<a href=\"index.php?page=$page&operation=create\">Добавить тип оплаты</a><br><br>");
      $header = "<table border=1 cellspacing=0>";
      $query = "select * from payment_types";
      $dbResult = null;
      $dbConnection = new DbSafeConnection();
      $dbConnection->safeExecute($query, $dbResult, false);
      if ($dbResult == null)
      {
        Display::append("Result is empty!");
      }
      else
      {        Display::append($header);
        Display::append("<tr><td>ID<td>Реквизиты<td><td>Включен");
        foreach ($dbResult as $row)
        {
          Display::append("<tr>");
          Display::append("<td>".$row["payment_type_id"]);
          Display::append("<td>".nl2br($row["requisites"]));
          Display::append("<td><a href=\"index.php?page=$page&operation=update&payment_type_id=".$row["payment_type_id"]."\">edit</a>");          if ($row["payment_type_state"]==1)
            $state = "ON";
          else
            $state ="";
          Display::append("<td>$state");
        }
        Display::append("</table>");
      }

    }

  }
?>
