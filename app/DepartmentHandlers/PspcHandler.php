<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers;

class PspcHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?lang=eng&SCR=Q&Sort=0';
    public $baseUrl = 'http://www.tpsgc-pwgsc.gc.ca/';
    public $ownerAcronym = 'pspc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main-in']//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

    // public function quarterToContractUrlTransform($contractUrl) {
    // 	return "http://disclosure.esdc.gc.ca/dp-pd/" . $contractUrl;
    // }

    // public function indexToQuarterUrlTransform($url) {
    // 	return "http://disclosure.esdc.gc.ca/dp-pd/" . $url;
    // }

    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/\s([0-9])</');
    }

    public function parseHtml($html)
    {

        // Service Canada doesn't include an "original contract value" on their entries. Only the current contract value is listed.

        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Description (more details):',
            'contractPeriodStart' => 'Contract Period - From',
            'contractPeriodEnd' => 'Contract Period - To',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Contract Value',
            'contractValue' => 'Total Amended Contract Value',
            'comments' => 'Comments:',
        ];

        $values = Helpers::genericXpathParser($html, "//table//th", "//table//td", 'to', $keyArray);

        

        return $values;
    }
}
