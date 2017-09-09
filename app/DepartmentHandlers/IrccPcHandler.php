<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers;

// Passport Canada (now part of IRCC)
class IrccPcHandler extends DepartmentHandler {

	public $indexUrl = 'http://www.cic.gc.ca/disclosure-divulgation/cont-eng.aspx';
	public $baseUrl = 'http://www.cic.gc.ca/';
	public $ownerAcronym = 'ircc-pc';

	// From the index page, list all the "quarter" URLs
	public $indexToQuarterXpath = "//main//ul/li/a/@href";

	public $multiPage = 0;

	public $quarterToContractXpath = "//main//table//td//a/@href";

	public function quarterToContractUrlTransform($contractUrl) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $contractUrl;
	}

	public function indexToQuarterUrlTransform($url) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
	}

	public $contractContentSubsetXpath = "//form[@id='aspnetForm']";

	public function fiscalYearAndQuarterFromIrccTitle($quarterHtml, $output ='year') {

		// Somewhat confusingly, IRCC indexes quarters by calendar year instead of by fiscal year.

		$quarterIndex = [
			'January 1 - March 31' => 4,
			'April 1 - June 30' => 1,
			'July 1 - September 30' => 2,
			'October 1 - December 31' => 3,
		];

		$fiscalYear = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
		$fiscalQuarter = '';


// \w
		$title = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/(.*)/');

		// Try to find one of the text labels in the title element, and match the Q1, Q2, etc. value:
		foreach($quarterIndex as $quarterLabel => $quarterKey) {
			if(strpos($title, $quarterLabel) !== false) {
				$fiscalQuarter = $quarterKey;
			}
		}

		// Correction to keep these aligned with fiscal-year based entries.
		// For example, when IRCC has "January 1 - March 31, 2016", other departments would have "2015-2016 Q4" and so the operative year would actually be 2015.
		// TODO - ask someone to doublecheck this logic.
		if($fiscalQuarter == 4) {
			$fiscalYear -= 1;
		}

		if($output == 'year') {
			return $fiscalYear;
		}
		else {
			return $fiscalQuarter;
		}

	}

	public function fiscalYearFromQuarterPage($quarterHtml, $quarterUrl) {

		return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'year');

	}

	public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl) {

		return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'quarter');

	}

	public static function parseHtml($html) {

		// Passport Canada has a typo in "Vender name"
		$keyArray = [
			'vendorName' => 'Vender Name:',
			'referenceNumber' => 'Reference Number:',
			'contractDate' => 'Contract Date:',
			'description' => 'Description of work:',
			'extraDescription' => 'Description (more details):',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract Period:',
			'deliveryDate' => 'Delivery Date:',
			'originalValue' => '',
			'contractValue' => 'Contract Value:',
			'comments' => 'Comments:',
		];

		return Helpers::genericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);

	}

}
