<?php
  class SubDomain
  {
    private $requestId;
    private $subDomain;
    private $domain;

    function __construct($requestId, $subDomain, $domain = Config::MAIN_DOMAIN)
    {
      $this->requestId = $requestId;
      $this->subDomain = $subDomain;
      $this->domain = $domain;
    }

    function getDomainName()
    {
      return $this->subDomain.".".$this->domain;
    }

    function getSubDomain()
    {
      return $this->subDomain;
    }

    function getRequestId()
    {
      return $this->requestId;
    }

  }
?>