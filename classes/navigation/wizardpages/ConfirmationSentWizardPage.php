<?php
  class ConfirmationSentWizardPage extends WizardPage
  {
    public function validate()
    {
      //if confirmation key is not valid at all
      if (!Validator::isConfirmation($md5, Strings::INCORRECT_CONFIRMATION_LINK)) return;

      //if request with this md5 exists
      if (!Validator::isDomainRequestExistsByMD5($md5, Strings::INCORRECT_CONFIRMATION_LINK)) return;

      //if fresh request with this md5 exists
      if (!Validator::isFreshDomainRequestExistsByMD5($md5, Strings::REGISTRATION_EXPIRED)) return;

      //if domain free
      if (Validator::isDomainFreeByMD5($md5, Strings::EXISTENT_SUBDOMAIN_NAME))
      {
        $dbConnection = new DbSafeConnection();
        $domain = $dbConnection->getDomainRequestNameByMD5($md5);
        $email = $dbConnection->getDomainRequestEmailByMD5($md5);
        $_SESSION['domain'] = $domain;
        $_SESSION['email'] = $email;
        $this->redirectToPage("page4");
      }
      else
      {        Display::append(Strings::REGISTRATION_NOT_CONFIRMED);      }
    }
    public function prevPage()
    {
    }

    public function nextPage()
    {
    }

    public function display()
    {
      $subDomain = $_SESSION['subdomain'];
      $email = $_SESSION['email'];
      if (!$subDomain || !$email) return;
      if (!Validator::isFreshSubDomainRequestAbsent($subDomain, $email, Strings::REGISTRATION_REQUEST_EXISTS)) return;
      $dbConnection = new DbSafeConnection();
      $md5 = $dbConnection->addSubDomainRequest($subDomain, $email);
      $link = "http://". Config::MAIN_DOMAIN . $this->pathToScript . $this->urlSymbol()
        . Navigation::PAGE."=page4&confirmation=$md5";
      Display::debug($link);
      //send e-mail and show status - email was sent successfully or not
      $subject = sprintf(Strings::CONFIRMATION_SUBJECT, $subDomain . "." . Config::MAIN_DOMAIN);
      $body = sprintf(Strings::CONFIRMATION_BODY, $subDomain . "." . Config::MAIN_DOMAIN, $link, $link);
      $success = DataProcessor::sendMail($email, $subject, $body);
      if ($success)
      {        Display::append(sprintf(Strings::REGISTRATION_EMAIL_SENT, $email));
      }

    }
  }
?>
