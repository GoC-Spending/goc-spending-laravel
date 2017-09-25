<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CsaHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.asc-csa.gc.ca/eng/publications/contracts.asp';
    public $baseUrl = 'http://www.asc-csa.gc.ca/';
    public $ownerAcronym = 'csa';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main-in']//table//ul/li/a/@href";

    public $areQuartersPaginated = true;
    public $quarterMultiPageXpath = "//div[@class='embedded-nav']//a/@href";

    public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    // Ignore the latest quarter that uses "open.canada.ca" as a link instead.
    // We'll need to retrieve those from the actual dataset.
    public function filterQuarterUrls($quarterUrls)
    {

        // Remove the new entries with "open.canada.ca"
        $quarterUrls = array_filter($quarterUrls, function ($url) {
            if (strpos($url, 'open.canada.ca') !== false) {
                return false;
            }
            return true;
        });

        return $quarterUrls;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // //*[@id="wb-main-in"]/p[2]/strong

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main-in']/p[2]/strong", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main-in']/p[2]/strong", '/-\s([0-9])</');
    }

    public function parseHtml($html)
    {

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ');
    }
}
