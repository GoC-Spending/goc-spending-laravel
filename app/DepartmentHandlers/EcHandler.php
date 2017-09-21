<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers;

class EcHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.ec.gc.ca/contracts-contrats/index.cfm?lang=En&state=reports';
    public $baseUrl = 'http://www.ec.gc.ca/contracts-contrats/';
    public $ownerAcronym = 'ec';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main']//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//div[@id='cn-centre-col-inner']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.ec.gc.ca/contracts-contrats/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.ec.gc.ca/contracts-contrats/" . $url;
    }

    public $contractContentSubsetXpath = "//div[@id='cn-centre-col-inner']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Helpers::xpathRegexComboSearch($quarterHtml, "//div[@id='cn-centre-col-inner']//h1", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Helpers::xpathRegexComboSearch($quarterHtml, "//div[@id='cn-centre-col-inner']//h1", '/([0-9])</');
    }

    public function parseHtml($html)
    {

        return Helpers::genericXpathParser($html, "//table//td[@scope='row']", "//table//td[@class='alignTopLeft']", ' to ');
    }
}
