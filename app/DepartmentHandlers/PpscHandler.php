<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class PpscHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.ppsc.gc.ca/eng/tra/pd-dp/contracts/reports.asp';
    public $baseUrl = 'http://www.ppsc.gc.ca/';
    public $ownerAcronym = 'ppsc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul[not(@class)]/li/a/@href";

    public $quarterToContractXpath = "//main//table//td//a/@href";


    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.ppsc.gc.ca/eng/tra/pd-dp/contracts/" . $contractUrl;
    }



    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.ppsc.gc.ca/eng/tra/pd-dp/contracts/" . $url;
    }


    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ');
    }
}
