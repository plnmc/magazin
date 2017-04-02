      <tr id='center-1lev-2v'>
        <td width=50% class='center-1lev-left' valign=top>
        <h3>Наши клиенты</h3>
        <table width = 100% border=0 cellspacing=0 cellpadding=0>
  			<tr><td><a href="http://raznoshop.com"><img src="images/clients/raznosvet.jpg"></a><br>Разносвет - интернет-магазин светотехники и электротехники. Лампы накаливания, энергосберегающие лампы, люминесцентные лампы. Светильники настенные, потолочные, люстры, бра, торшеры. Электроустановочные изделия, выключатели, розетки, удлинители, вилки, разъемы, трансформаторы.<hr>
  			<tr><td><a href="http://shop.aktiformulanpk.ru/"><img src="images/clients/aktiformula.png"></a><br>Интернет-магазин одного из лидирующих производителей спортивного питания на российском рынке - компании "Актиформула". В магазине представлено спортивное питание и лечебное питание. Протеины, гейнеры, жиросжигатели, аминокислоты, креатин, энергетики, экдистерон. Энтеральное питание, СБКС (смеси белковые композитны сухие). В магазине также представлены линейки продукции компаний G.E.O.N., Wirud и других.<hr>
  			<tr><td><a href="http://allbrandsstore.ru/"><img src="images/clients/allbrands.bmp"></a><br>AllBrandsStore. Магазин сумок - копий всемирно известных брендов: Balenciaga, Chanel, Chloe, Christian Dior, Dolce&Gabbana, Fendi, Gucci, Hermes, Jimmy Choo, Lancel, Louis Vuitton, Marc Jacobs, Mulberry. Наш магазин брендовых сумок следит за последними тенденциями в мире моды и представляет самые последние модели известных сумок.<hr>
        </table></td>
        <td class='center-1lev-divider'>&nbsp;</td>
        <td width=50% class='center-1lev-right' valign=top>
        <h3>Новые поступления</h3>
<?php
      $clients = SystemOperations::getClientList();
      foreach ($clients as $client)
      {
        echo "<b>".date("d.m.Y", $client['created_date'])."</b>";
        echo "&nbsp;&nbsp;<a href=\"http://".$client['domain_name']."\">http://".$client['domain_name']."</a>&nbsp;&nbsp;".$client['shop_name']."<br>";
      }
?>
      </tr>