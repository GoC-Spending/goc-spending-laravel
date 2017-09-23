<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class PcHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.pc.gc.ca/apps/pdc/index_e.asp?oqYEAR=&oqQUARTER=';
    public $baseUrl = 'http://www.pc.gc.ca/';
    public $ownerAcronym = 'pc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";



    public $quarterToContractXpath = "//main//table//td//a/@href";

    
    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.pc.gc.ca/apps/pdc/" . $contractUrl;
    }
    

    
    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.pc.gc.ca/apps/pdc/" . $url;
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
            'description' => 'Description of work:',
            'extraDescription' => 'Additional Comments:',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        return Parsers::extractContractDataViaGenericXpathParser($html, "//form//label", "//form//p", ' to ', $keyArray);
    }
}
