<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers;

class SscHandler extends DepartmentHandler {

	public $indexUrl = 'http://www.ssc-spc.gc.ca/pages/contracts-contrats-eng.html';
	public $baseUrl = 'http://www.ssc-spc.gc.ca/';
	public $ownerAcronym = 'ssc';

	// From the index page, list all the "quarter" URLs
	public $indexToQuarterXpath = "//div[@id='wb-main-in']//ul/li/a/@href";

	public $multiPage = 0;

	public $quarterToContractXpath = "//div[@id='wb-main-in']//table//td//a/@href";

	/*
	public function quarterToContractUrlTransform($contractUrl) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $contractUrl;
	}

	public function indexToQuarterUrlTransform($url) {
		return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
	}*/


	// Ignore the latest quarter that uses "open.canada.ca" as a link instead.
	// We'll need to retrieve those from the actual dataset.
	public function filterQuarterUrls($quarterUrls) {

		// Remove the new entries with "open.canada.ca"
		$quarterUrls = array_filter($quarterUrls, function($url) {
			if(strpos($url, 'open.canada.ca') !== false) {
				return false;
			}
			return true;
		});

		return $quarterUrls;
	}


	public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

	public function fiscalYearAndQuarterFromTitle($quarterHtml, $output ='year') {

		// Somewhat confusingly, IRCC indexes quarters by calendar year instead of by fiscal year.

		$quarterIndex = [
			'Fourth Quarter' => 4,
			'First Quarter' => 1,
			'Second Quarter' => 2,
			'Third Quarter' => 3,
		];

		$fiscalYear = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
		$fiscalQuarter = '';


// \w
		$title = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/(.*)/');

		// Try to find one of the text labels in the title element, and match the Q1, Q2, etc. value:
		foreach($quarterIndex as $quarterLabel => $quarterKey) {
			if(strpos($title, $quarterLabel) !== false) {
				$fiscalQuarter = $quarterKey;
				break;
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

		return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'year');

	}

	public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl) {

		return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'quarter');

	}

	public static function parseHtml($html) {

		$keyArray = [
			'vendorName' => 'Vendor Name',
			'referenceNumber' => 'Reference Number',
			'contractDate' => 'Contract Date',
			'description' => 'Description',
			'extraDescription' => 'Detailed Description',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract Period',
			'deliveryDate' => 'Delivery Date',
			'originalValue' => 'Original Contract Value',
			'contractValue' => 'Contract Value',
			'comments' => 'Comments',
		];

		return Helpers::genericXpathParser($html, "//table//th[@scope='row']", "//table//td", ' to ', $keyArray);

	}

}
