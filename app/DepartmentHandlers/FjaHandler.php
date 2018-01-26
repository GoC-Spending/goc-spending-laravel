<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class FjaHandler extends DepartmentHandler
{
    public $indexUrl = 'http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/reports-rapports-eng.aspx';
    public $baseUrl = 'http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/';
    public $ownerAcronym = 'fja';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@id='wb-main']//table//td//a/@href";

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
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9])</');
    }

    public function parseHtml($html) {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//table//th[@scope='row']",
            // Values
            "//table//td[@class='align-left']",
            // Period split
            ' to/à ',
            // Keys
            [
                'vendorName' => 'Vendor Name:',
                'referenceNumber' => 'Reference Number:',
                'contractDate' => 'Contract Date:',
                'description' => 'Description of work:',
                'extraDescription' => 'Description (more details):',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period:',
                'deliveryDate' => 'Delivery Date:',
                'originalValue' => 'Original Contract Value:',
                'contractValue' => 'Contract Value:',
                'comments' => 'Comments:',
                'formerPublicServant' => 'Former Public Servant:',
            ]
        );

        // Convert formerPublicServant to boolean
        if (array_key_exists('formerPublicServant', $data)) {
            $data['formerPublicServant'] = filter_var($data['formerPublicServant'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }
}
