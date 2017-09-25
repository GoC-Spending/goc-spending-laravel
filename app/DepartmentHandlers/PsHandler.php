<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class PsHandler extends DepartmentHandler
{

    public $indexUrl = 'https://www.publicsafety.gc.ca/cnt/trnsprnc/cntrcts/qrts-en.aspx';
    public $baseUrl = 'https://www.publicsafety.gc.ca/';
    public $ownerAcronym = 'ps';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $quarterToContractXpath = "//main//table//td//a/@href";


    public function quarterToContractUrlTransform($contractUrl)
    {
        return "https://www.publicsafety.gc.ca/cnt/trnsprnc/cntrcts/" . $contractUrl;
    }



    public function indexToQuarterUrlTransform($url)
    {
        return "https://www.publicsafety.gc.ca/cnt/trnsprnc/cntrcts/" . $url;
    }


    public $contractContentSubsetXpath = "//main";

    public function fiscalYearAndQuarterFromTitle($quarterUrl, $output = 'year')
    {

//        Public Safety doesn't include the contract year and quarter in the HTML of the quarter page, but it includes it in the quarter page URL.
//        For example,
//        https://www.publicsafety.gc.ca/cnt/trnsprnc/cntrcts/qrts-dtls-en.aspx?y=2015&q=4

        $fiscalYear = '';
        $fiscalQuarter = '';

        $matches = [];
        $pattern = '/y=([0-9]{4})&q=([0-9])/';

        preg_match($pattern, $quarterUrl, $matches);


        if (isset($matches[1]) && isset($matches[2])) {
            $fiscalYear = $matches[1];
            $fiscalQuarter = $matches[2];
        }


        if ($output == 'year') {
            return $fiscalYear;
        } else {
            return $fiscalQuarter;
        }
    }

    public function fiscalYearFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterUrl, 'year');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterUrl, 'quarter');
    }

    public function parseHtml($html)
    {

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' - ');
    }
}
