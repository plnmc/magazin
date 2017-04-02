<?php
  class OldShopsObjectPage extends ShopAdminObjectPage
  {
    protected $listQuery;
    protected $domainNamesQuery;
    protected $dbNamesQuery;
    protected $shopIdsQuery;
    protected $pageHeader;

    function __construct()
    {
      $this->domainNamesQuery = "select domain_names.domain_name as domain_name from shops, domain_names, shop_types where shops.shop_id = domain_names.shop_id and shops.shop_type_id = shop_types.shop_type_id and shops.shop_type_id = 1 and shops.shop_state_id = 0 and DATE_SUB(NOW(), INTERVAL ".Config::OLD_SHOPS_PERIOD_MONTHS." MONTH) > shops.next_bill_date";
      $this->dbNamesQuery = "select dbs.name as db_name from dbs, shops, domain_names, shop_types where shops.db_id=dbs.db_id and shops.shop_id = domain_names.shop_id and shops.shop_type_id = shop_types.shop_type_id and shops.shop_type_id = 1 and shops.shop_state_id = 0 and DATE_SUB(NOW(), INTERVAL ".Config::OLD_SHOPS_PERIOD_MONTHS." MONTH) > shops.next_bill_date";
      $this->shopIdsQuery = "select shops.shop_id as shop_id from shops, domain_names, shop_types where shops.shop_id = domain_names.shop_id and shops.shop_type_id = shop_types.shop_type_id and shops.shop_type_id = 1 and shops.shop_state_id = 0 and DATE_SUB(NOW(), INTERVAL ".Config::OLD_SHOPS_PERIOD_MONTHS." MONTH) > shops.next_bill_date";
      $this->pageHeader = "Магазины, просроченные на ".Config::OLD_SHOPS_PERIOD_MONTHS." месяцев и более (только отключенные)";
    }

  }
?>