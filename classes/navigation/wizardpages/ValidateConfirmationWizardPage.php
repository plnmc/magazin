<?php
  class ValidateConfirmationWizardPage extends WizardPage
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
      $md5 = $_GET['confirmation'];
      if (!Validator::fullConfirmationCheck($md5)) return;
      $_SESSION['md5'] = $md5;
      $this->redirectToPage("page5");
    }
  }
?>
