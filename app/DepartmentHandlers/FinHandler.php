<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class FinHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.fin.gc.ca/contracts-contrats/quarter-trimestre.aspx?lang=1';
    public $baseUrl = 'http://www.fin.gc.ca/';
    public $ownerAcronym = 'fin';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main-in']//ul/li/a/@href";



    public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.fin.gc.ca/contracts-contrats/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.fin.gc.ca/contracts-contrats/" . $url;
    }

    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//span[@id='ContentPlaceHolder1_lblQuarterTitle']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//span[@id='ContentPlaceHolder1_lblQuarterTitle']", '/\s([0-9])</');
    }

    public function parseHtml($html)
    {
        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => '',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value',
            'contractValue' => 'Contract Value',
            'comments' => 'Comments:',
        ];

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//div[@id='ContentPlaceHolder1_divDataDetail']//div[@class='span-2']", "//div[@id='ContentPlaceHolder1_divDataDetail']//div[@class='span-3']", 'to', $keyArray);

        

        return $values;
    }
}
