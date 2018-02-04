<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class JustHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.justice.gc.ca/eng/trans/pd-dp/contra/rep-rap.asp';
    public $baseUrl = 'http://www.justice.gc.ca/';
    public $ownerAcronym = 'just';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";



    public $quarterToContractXpath = "//main//table//td//a/@href";

    
    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.justice.gc.ca/eng/trans/pd-dp/contra/" . $contractUrl;
    }
    

    
    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.justice.gc.ca/eng/trans/pd-dp/contra/" . $url;
    }
    

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // Recently broken by the "Moved to open.canada.ca" message, which also uses an h2
        $quarterHtml = str_replace('<h2>Important Information</h2>', '', $quarterHtml);

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        // Recently broken by the "Moved to open.canada.ca" message, which also uses an h2
        $quarterHtml = str_replace('<h2>Important Information</h2>', '', $quarterHtml);

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9])</');
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

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);
    }
}
