<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CspsHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.csps-efpc.gc.ca/transparency/contract/contractquarter-eng.aspx';
    public $baseUrl = 'http://www.csps-efpc.gc.ca/';
    public $ownerAcronym = 'csps';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    // Pagination
    public $areQuartersPaginated = true;
    public $includeFirstPaginatedPage = true;
    public $quarterMultiPageXpath = "//div[@id='mainContent_plcPaging']//a/@href";

    // Mystery 404 page for one of the contracts
    public $knownBrokenContractUrls = [
        '/transparency/contract/contractdetail-eng.aspx?id=11364&quar=2&an=2013-2014',
        '/transparency/contract/contractdetail-eng.aspx?id=8805&quar=1&an=2009-2010',
        '/transparency/contract/contractdetail-eng.aspx?id=8756&quar=1&an=2009-2010',
        '/transparency/contract/contractdetail-eng.aspx?id=8756&quar=1&an=2009-2010',
    ];


    public $quarterToContractXpath = "//main//table//td//a/@href";

    
    // public function quarterToContractUrlTransform($contractUrl)
    // {
    //     return "http://www.csps-efpc.gc.ca/transparency/contract/" . $contractUrl;
    // }
    
    

    
    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.csps-efpc.gc.ca/transparency/contract/" . $url;
    }
    

    public $contractContentSubsetXpath = "//div[@id='mainContent_contdet']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // //*[@id="pageForm"]/p[2]/strong

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h1[@id='wb-cont']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        // //form[@id='pageForm']/p[2]/strong
        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h1[@id='wb-cont']", '/([0-9])</');
    }

    public function parseHtml($html)
    {

        return Parsers::extractContractDataViaGenericXpathParser($html, "//div[@class='col-xs-6'][1]", "//div[@class='col-xs-6'][2]", ' to ');
    }
}
