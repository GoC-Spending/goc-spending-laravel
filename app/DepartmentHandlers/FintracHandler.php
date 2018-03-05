<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class FintracHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.fintrac-canafe.gc.ca/transp/PD/cd/rpt-eng.asp';
    public $baseUrl = 'http://www.fintrac-canafe.gc.ca/';
    public $ownerAcronym = 'fintrac';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $quarterToContractXpath = "//main//table//td//a/@href";


    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $contractUrl;
    }



    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $url;
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

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);
    }
}
