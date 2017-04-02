<?php
  class FinishWizardPage extends WizardPage
  {
    public function validate()
    {
    }

    public function prevPage()
    {
    }

    public function nextPage()
    {
    }

    public function display()
    {
      $md5 = $_SESSION['md5'];
      $shopName = $_SESSION['shop_name'];
      $name = $_SESSION['name'];
      $surname = $_SESSION['surname'];
      $phone = $_SESSION['phone'];
      $city = $_SESSION['city'];
      $pass = $_SESSION['pass'];

      if (!Validator::fullConfirmationCheck($md5)) return;
      if (!Validator::lengthIsOk($shopName, 1, 256, Strings::LENGTH_SHOP_NAME)) return;
      if (!Validator::isName($name, Strings::LENGTH_NAME, Strings::INCORRECT_NAME)) return;
      if (!Validator::isName($surname, Strings::LENGTH_SURNAME, Strings::INCORRECT_SURNAME)) return;
      if (!Validator::isPassword($pass, $pass, Strings::LENGTH_PASS, Strings::INCORRECT_PASS, Strings::NOT_MATCH_PASS)) return;

      //create db and subdomain in plesk
      //deploy shop:
      $shopInfo = SystemOperations::createShop($md5, $shopName, $name, $surname, $phone, $city, $pass);
      if ($shopInfo != false)
      {
        $domain = $shopInfo['domain'];
        $adminDir = $shopInfo['admin_dir'];
        $email = $shopInfo['email'];
        $welcomeDisplay = "";
        $welcomeDisplay .= "<p><b>".Strings::CONGRATULATIONS."</b>";
        $welcomeDisplay .= "<br><br>";
        $welcomeDisplay .= sprintf(Strings::LINK_TO_SHOP, "<a href='http://$domain'>http://$domain</a>");
        $welcomeDisplay .= "<br>";
        $welcomeDisplay .= sprintf(Strings::LINK_TO_ADMIN, "<a href='http://$domain/$adminDir'>http://$domain/$adminDir</a>");
        $welcomeDisplay .= "<br>";
        $welcomeDisplay .= sprintf(Strings::LOGIN_INFO, $email);
        $welcomeDisplay .= "<br>";
        $welcomeEmail = $welcomeDisplay;
        $welcomeDisplay .= Strings::PASS_INFO;
        $welcomeEmail .= sprintf(Strings::PASS_INFO2, $pass);
        $welcomeEmail .= "<br>".nl2br(Strings::EMAIL_OFFER);
        Display::append($welcomeDisplay);

        //send e-mail
        $subject = Strings::CONGRATULATIONS;
        $body = $welcomeEmail;
        DataProcessor::sendMail($email, $subject, $body);
        $body = "<p><b>".Strings::COPY_TO_ADMIN."</b><hr>".$body;
        DataProcessor::sendMail(Config::SUPPORT_EMAIL, Strings::CUSTOMER_CREATED_SHOP, $body);
      }


    }

  }
?>
