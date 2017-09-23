<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class ScHandler extends DepartmentHandler
{

    public $indexUrl = 'http://disclosure.servicecanada.gc.ca/dp-pd/prdlstcdn-eng.jsp?site=3&section=2';
    public $baseUrl = 'http://disclosure.servicecanada.gc.ca/';
    public $ownerAcronym = 'sc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//main//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://disclosure.servicecanada.gc.ca/dp-pd/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://disclosure.servicecanada.gc.ca/dp-pd/" . $url;
    }

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//h2", '/\s([0-9])</');
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
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => '',
            'contractValue' => 'Current Contract Value:',
            'comments' => 'Comments:',
        ];

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", 'to', $keyArray);

        

        return $values;
    }
}
