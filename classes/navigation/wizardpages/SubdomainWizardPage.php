<?php
  class SubdomainWizardPage extends WizardPage
  {
    public function validate()
    {
      $subDomain = $_POST['subdomain'];
      $_SESSION['subdomain'] = $subDomain;
      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME,
        Strings::INCORRECT_SUBDOMAIN_NAME)) return;
      if (!Validator::isSubDomainFree($subDomain, Strings::EXISTENT_SUBDOMAIN_NAME)) return;
      $_SESSION['subdomain'] = strtolower($subDomain);
      $this->redirectToPage("page2");
    }

    public function prevPage()
    {
    }

    public function nextPage()
    {
    }

    public function display()
    {
/*      Display::append("<p>На сервере ведутся профилактические работы!<br>");*/
      if ($_SERVER['REQUEST_METHOD']=="POST")
      {
        $this->validate();
      }
      else
      {      Display::append("<p>Создайте Ваш интернет-магазин самостоятельно прямо сейчас! <br>"
        ."Просто введите название вашего интернет-магазина английскими буквами (например, moimagazin) и нажмите кнопку \"Далее\".<br>"
        ." После подтверждения регистрации вам будет доступен полностью функциональный интернет-магазин. <br><br>"        ."Вы сможете изменить доменное имя магазина после создания, обратившись в техподдержку vsemagazin@mail.ru<br>");      }
      $subDomain = $_SESSION['subdomain'];
      Display::append("<form action='".$this->pathToScript."' method=post>");
      Display::append("<p><b>Введите адрес вашего будущего магазина:</b><br>");

      Display::append("<input type=text name=subdomain value='$subDomain'>");
      Display::append("." . Config::MAIN_DOMAIN);
      Display::append("<input type=hidden name=".Navigation::PAGE." value=page1>");
      Display::append("<br><input type=submit value='Далее >>'></form>");
    }
  }
?>
