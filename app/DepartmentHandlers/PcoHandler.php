<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class PcoHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.pco-bcp.gc.ca/dc/Contracts_FYQuarter_Listings.asp?lang=eng';
    public $baseUrl = 'http://www.pco-bcp.gc.ca/';
    public $ownerAcronym = 'pco';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main-in']//ul/li/a/@href";



    public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.pco-bcp.gc.ca/dc/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.pco-bcp.gc.ca/dc/" . $url;
    }

    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main-in']//h3", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main-in']//h3", '/\s([0-9])</');
    }

    public function parseHtml($html)
    {
        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Brief Description (if applicable):',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value',
            'contractValue' => 'Total Amended Contract Value:',
            'comments' => 'Comments:',
        ];

//        //*[@id="wb-main-in"]/table/tbody/tr[2]/td[2]
//        Slightly challenging, both the headings and cells for PCO are both td's.
//        Since the second one doesn't have a class, we need to use something else to exclude the first ones (which are headings/keys).

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//table//td[@class='grantHeader']", "//table//tr/td[2]", 'to', $keyArray);

        

        return $values;
    }
}
