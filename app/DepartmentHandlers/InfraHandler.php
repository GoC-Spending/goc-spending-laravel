<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class InfraHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.infrastructure.gc.ca/pd-dp/pdc-dpm/reports-rapports-eng.html';
    public $baseUrl = 'http://www.infrastructure.gc.ca/';
    public $ownerAcronym = 'infra';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main-in']//ul/li/a/@href";



    public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

//    public function quarterToContractUrlTransform($contractUrl)
//    {
//        return "http://www.fin.gc.ca/contracts-contrats/" . $contractUrl;
//    }
//
//    public function indexToQuarterUrlTransform($url)
//    {
//        return "http://www.fin.gc.ca/contracts-contrats/" . $url;
//    }

    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    public function fiscalYearAndQuarterFromTitle($quarterHtml, $output = 'year')
    {

        // Somewhat confusingly, IRCC indexes quarters by calendar year instead of by fiscal year.

        $quarterIndex = [
            'January to March' => 4,
            'April to June' => 1,
            'July to September' => 2,
            'October to December' => 3,
        ];

        $fiscalYear = Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
        $fiscalQuarter = '';


// \w
        $title = Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/(.*)/');

        // Try to find one of the text labels in the title element, and match the Q1, Q2, etc. value:
        foreach ($quarterIndex as $quarterLabel => $quarterKey) {
            if (strpos($title, $quarterLabel) !== false) {
                $fiscalQuarter = $quarterKey;
                break;
            }
        }

        if ($output == 'year') {
            return $fiscalYear;
        } else {
            return $fiscalQuarter;
        }
    }

    public function fiscalYearFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'year');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'quarter');
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

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);


//        Check for random amendments included in the comments, such as,
//        "This contract includes an amendment in the amount of $8,520.14, bringing the total contract value to $11,173.15."

        if ($values['comments']) {
            $matches = [];
            $pattern = '/bringing the total contract value to \$?([0-9]{1,3},([0-9]{3},)*[0-9]{3}|[0-9]+)(.[0-9][0-9])?/';
//            with thanks to
//            http://regexlib.com/Search.aspx?k=currency&AspxAutoDetectCookieSupport=1
//            Dollars show up as $matches[1], and cents show up as $matches[3]

            preg_match($pattern, $values['comments'], $matches);


            if (isset($matches[1]) && isset($matches[3])) {
                $currentValue = $matches[1] . $matches[3];

                $values['originalValue'] = $values['contractValue'];
                $values['contractValue'] = $currentValue;
            }
        }

        

        return $values;
    }
}
