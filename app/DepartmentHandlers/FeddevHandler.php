<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class FeddevHandler extends DepartmentHandler
{
    public $indexUrl = 'http://www.feddevontario.gc.ca/app/fdo/cr/lstQrtrs.do?lang=eng';
    public $baseUrl = 'http://www.feddevontario.gc.ca/app/fdo/cr/';
    public $ownerAcronym = 'feddev';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@role='main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@role='main']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@role='main']";

    public function indexToQuarterUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function quarterToContractUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        $startMonth = Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//h1", '/(January|April|July|October)/');
        $year = (int)Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//h1", '/([0-9]{4})/');

        if ($startMonth == 'January') {
            $year -= 1;
        }

        return (string)$year;
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        $startMonth = Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//h1", '/(January|April|July|October)/');
        $year = (int)Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//h1", '/([0-9]{4})/');

        switch($startMonth) {
            case 'January':
                return '4';
            case 'April':
                return '1';
            case 'July':
                return '2';
            case 'October':
                return '3';
        }
    }

    public function parseHtml($html) {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//table//td[1]",
            // Values
            "//table//td[2]",
            // Period split
            ' - ',
            // Keys
            [
                'vendorName' => 'Vendor Name:',
                'referenceNumber' => 'Reference Number:',
                'contractDate' => 'Contract Date:',
                'description' => 'Description:',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period / Delivery Date:',
                'deliveryDate' => 'Delivery Date:',
                'originalValue' => 'Original Contract Value:',
                'contractValue' => 'Contract Value:',
                'comments' => 'Comments:',
                'additionalComments' => 'Additional Comments:',
            ]
        );

        // Since the data is in the same field, set them that way in the output.
        $data['deliveryDate'] = $data['contractPeriodRange'];

        return $data;
    }
}