<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CfiaHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.inspection.gc.ca/active/eng/agen/proactive/contra/raprte.asp';
    public $baseUrl = 'http://www.inspection.gc.ca/';
    public $ownerAcronym = 'cfia';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//main//table//td//a/@href";

    // Quarters are returned as absolute URLs, so no quarterToContractUrlTransform needed.
    /*
	public function quarterToContractUrlTransform($contractUrl) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $contractUrl;
	}
	*/

    // Ignore the latest quarter that uses "open.canada.ca" as a link instead.
    // We'll need to retrieve those from the actual dataset.
    public function filterQuarterUrls($quarterUrls)
    {

        // Remove the new entries with "open.canada.ca"
        $quarterUrls = array_filter($quarterUrls, function ($url) {
            if (strpos($url, 'open.canada.ca') !== false) {
                return false;
            }
            return true;
        });

        return $quarterUrls;
    }

    /*
	public function indexToQuarterUrlTransform($url) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
	}
	*/

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearAndQuarterFromIrccTitle($quarterHtml, $output = 'year')
    {

        // Somewhat confusingly, IRCC indexes quarters by calendar year instead of by fiscal year.

        $quarterIndex = [
            'January 1 - March 31' => 4,
            'April 1 - June 30' => 1,
            'July 1 - September 30' => 2,
            'October 1 - December 31' => 3,
        ];

        $fiscalYear = Parsers::xpathRegexComboSearch($quarterHtml, "//main/h1", '/([0-9]{4})/');
        $fiscalQuarter = '';


// \w
        $title = Parsers::xpathRegexComboSearch($quarterHtml, "//main/h1", '/(.*)/');

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

        return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'year');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'quarter');
    }

    public function parseHtml($html)
    {

        $keyArray = [
            'vendorName' => 'Vendor Name',
            'referenceNumber' => 'Reference Number',
            'contractDate' => 'Date',
            'description' => 'Description of work',
            'extraDescription' => 'Detailed Description',
            'contractPeriodStart' => 'Contract Start Date',
            'contractPeriodEnd' => 'Contract End Date',
            'contractPeriodRange' => '',
            'deliveryDate' => 'Delivery Date',
            'originalValue' => 'Original Contract Value',
            'contractValue' => 'Contract Value',
            'comments' => 'Comments',
        ];

        // CFIA has occasional HTML errors, such as
        // http://www.inspection.gc.ca/active/scripts/agen/proactive/contra/contrarport.asp?lang=e&yr=2016-2017&q=2&contraID=17443&yid=13
        // In these cases the data isn't properly returned from the server, so the parsed data will be missing entries.

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th[@scope='row']", "//table//td", ' to ', $keyArray);
    }
}
