<?php
  class EmailWizardPage extends WizardPage
  {
    public function validate()
    {
      $email = $_POST['email'];
      $_SESSION['email'] = $email;
      if (!Validator::isEmail($email, Strings::INCORRECT_EMAIL)) return;

      //validate domain name
      $subDomain = $_SESSION['subdomain'];
      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME,
        Strings::INCORRECT_SUBDOMAIN_NAME)) return;
      if (!Validator::isSubDomainFree($subDomain, Strings::EXISTENT_SUBDOMAIN_NAME)) return;

      //validate email
      if (!Validator::isEmail($email, Strings::INCORRECT_EMAIL)) return;

      $this->redirectToPage("page3");
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
      $email = $_SESSION['email'];
      Display::append("<form action='".$this->pathToScript."' method=post>");
      Display::append("<p><b>Введите Ваш e-mail:</b><br><input type=text name=email value='$email'>");
      Display::append("<input type=hidden name=".Navigation::PAGE." value=page2>");
      Display::append("<br><input type=submit value='Создать магазин!'></form>");
    }
  }
?>
