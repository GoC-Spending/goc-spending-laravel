<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CannorHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.cannor.gc.ca/prodis/cntrcts/rprts-eng.asp';
    public $baseUrl = 'http://www.cannor.gc.ca/';
    public $ownerAcronym = 'cannor';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@class='center']//ul/li/a/@href";


    public $quarterToContractXpath = "//div[@class='center']//table//td//a/@href";


    // public function quarterToContractUrlTransform($contractUrl)
    // {
    //     return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $contractUrl;
    // }



    // public function indexToQuarterUrlTransform($url)
    // {
    //     return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $url;
    // }


    public $contractContentSubsetXpath = "//div[@class='center']";



    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of Work:',
            'extraDescription' => '',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' - ', $keyArray);
    }
}
