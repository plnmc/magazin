      <tr id='center-1lev-2v' colspan="3">
        <td  class='center-1lev-another'  valign='top'>
		<div class='center-title' align='center'>Создать магазин</div><br>
<?php
  $wizardNavigation = new WizardNavigation($_SERVER["PHP_SELF"]."?link=create");
  $wizardNavigation->display();
  Display::showInPage();
?>
		</td>
      </tr>
