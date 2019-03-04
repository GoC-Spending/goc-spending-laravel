<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class AgrHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.agr.gc.ca/eng/about-us/planning-and-reporting/proactive-disclosure/disclosure-of-contracts-over-10000/?id=1353352471596';
    public $baseUrl = 'http://www.agr.gc.ca/';
    public $ownerAcronym = 'agr';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $quarterToContractXpath = "//main//table//td//a/@href";

    /*
    public function quarterToContractUrlTransform($contractUrl) {
        return "http://www.ec.gc.ca/contracts-contrats/" . $contractUrl;
    }
    */

    /*
    public function indexToQuarterUrlTransform($url) {
        return "http://www.ec.gc.ca/contracts-contrats/" . $url;
    }
    */

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//table//caption", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//table//caption", '/([0-9])</');
    }

    public function parseHtml($html)
    {

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th[@scope='row']", "//table//td", ' to ');
    }
}
