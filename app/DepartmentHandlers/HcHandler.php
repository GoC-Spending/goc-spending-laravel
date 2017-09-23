<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;

use App\Helpers\Parsers;

class HcHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.contracts-contrats.hc-sc.gc.ca/cfob/mssid/contractdisc.nsf/webGetbyperiod?OpenView&Count=1000&ExpandAll&lang=eng&';
    public $baseUrl = 'http://www.contracts-contrats.hc-sc.gc.ca/';
    public $ownerAcronym = 'hc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@class='center']//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//div[@class='center']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.contracts-contrats.hc-sc.gc.ca/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.contracts-contrats.hc-sc.gc.ca/" . $url;
    }

    public $contractContentSubsetXpath = "//div[@class='center']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h1", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h1", '/([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        // Service Canada doesn't include an "original contract value" on their entries. Only the current contract value is listed.

        $keyArray = [
            'vendorName' => 'Vendor Name',
            'referenceNumber' => 'Reference Number',
            'contractDate' => 'Contract Date',
            'description' => 'Description',
            'extraDescription' => 'Document Type',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period',
            'deliveryDate' => 'Delivery Date',
            'originalValue' => 'Original Contract Value',
            'contractValue' => 'Overall Contract Value',
            'comments' => 'Comments',
        ];

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//h2", "//p", 'to', $keyArray);

        

        return $values;
    }
}
