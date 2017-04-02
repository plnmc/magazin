<?php
  class PersonalInfoWizardPage extends WizardPage
  {
    public function validate()
    {
      $md5 = $_SESSION['md5'];
      $shopName = $_POST['shop_name'];
      $name = $_POST['name'];
      $surname = $_POST['surname'];
      $phone = $_POST['phone'];
      $city = $_POST['city'];
      $pass = $_POST['pass'];
      $pass2 = $_POST['pass2'];

      $_SESSION['shop_name'] = $shopName;
      $_SESSION['name'] = $name;
      $_SESSION['surname'] = $surname;
      $_SESSION['phone'] = $phone;
      $_SESSION['city'] = $city;
      $_SESSION['pass'] = $pass;

      $dbConnection = new DbSafeConnection();
      $domain = $dbConnection->getDomainRequestNameByMD5($md5);
      $email = $dbConnection->getDomainRequestEmailByMD5($md5);

      if (!Validator::isDomainFree($domain, Strings::EXISTENT_SUBDOMAIN_NAME)) return;
      if (!Validator::isEmail($email, Strings::INCORRECT_EMAIL)) return;
      if (!Validator::isPassword($pass, $pass2, Strings::LENGTH_PASS, Strings::INCORRECT_PASS, Strings::NOT_MATCH_PASS)) return;
      if (!Validator::lengthIsOk($shopName, 1, 256, Strings::LENGTH_SHOP_NAME)) return;
      if (!Validator::isName($name, Strings::LENGTH_NAME, Strings::INCORRECT_NAME)) return;
      if (!Validator::isName($surname, Strings::LENGTH_SURNAME, Strings::INCORRECT_SURNAME)) return;
      if (!Validator::isPhone($phone, Strings::INCORRECT_PHONE)) return;
      if (!Validator::isCity($city, Strings::INCORRECT_CITY, Strings::INCORRECT_CITY)) return;

      $this->redirectToPage("page6");
    }
    public function prevPage()
    {
    }

    public function nextPage()
    {
    }

    public function display()
    {
      if ($_SERVER['REQUEST_METHOD']=="POST")
      {
        $this->validate();
      }
      $shopName = $_SESSION['shop_name'];
      $name = $_SESSION['name'];
      $surname = $_SESSION['surname'];
      $phone = $_SESSION['phone'];
      $city = $_SESSION['city'];
      $md5 = $_SESSION['md5'];
      if (!Validator::fullConfirmationCheck($md5)) return;

      $dbConnection = new DbSafeConnection();
      $domain = $dbConnection->getDomainRequestNameByMD5($md5);
      $email = $dbConnection->getDomainRequestEmailByMD5($md5);

      Display::append("<form action='".$this->pathToScript."' method=post>");
      Display::append("<p>Ваш электронный адрес <b>$email</b> успешно подтвержден!<br>");
      Display::append("<p>Ваш магазин будет доступен по адресу: <b>$domain</b><br>");
      Display::append("<p><b>Заполните эту форму, чтобы создать магазин:</b><br>");
      Display::append("<table border=0>");
      Display::append("<tr><td>E-mail:<td>$email");
      Display::append("<tr><td>Введите желаемый пароль для управления магазином: <td><input type=password name=pass>");
      Display::append("<tr><td>Повторите пароль: <td><input type=password name=pass2>");
      Display::append("<tr><td>Название магазина (например, Электротехника): <td><input type=text name=shop_name value='$shopName'>");
      Display::append("<tr><td>Ваше имя: <td><input type=text name=name value='$name'>");
      Display::append("<tr><td>Ваша фамилия: <td><input type=text name=surname value='$surname'>");
      Display::append("<tr><td>Контактный телефон (указывайте код города): <td><input type=text name=phone value='$phone'>");
      Display::append("<tr><td>Город: <td><input type=text name=city value='$city'>");
      Display::append("</table>");
      Display::append("<input type=hidden name=".Navigation::PAGE." value=page5>");
      Display::append('<p>Нажимая кнопку "Создать интернет-магазин", вы подтверждаете, что прочитали и принимаете следующие правила:'.Strings::RULES);
      Display::append("<p><p><b>Создание магазина займет около одной минуты!</b><br><input type=submit value='Создать магазин!'></form>");
    }
  }
?>
