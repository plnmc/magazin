<?php
  class WizardNavigation extends Navigation
  {
    public function __construct($pathToScript)
    {
      $this->pagesArray = array (
        "page1"=> array ("type"=>"SubdomainWizardPage"),
        "page2"=> array ("type"=>"EmailWizardPage"),
        "page3"=> array ("type"=>"ConfirmationSentWizardPage"),
        "page4"=> array ("type"=>"ValidateConfirmationWizardPage"),
        "page5"=> array ("type"=>"PersonalInfoWizardPage"),
        "page6"=> array ("type"=>"FinishWizardPage")
        );
      parent::__construct($pathToScript);
    }

    protected function displayCommonPart()
    {
      //Display::append("Internet-shop for free!<br><br>");    }
  }
?>
