---
title: 'Analysis NEW'
date: 2018-11-14T19:02:50-07:00
draft: false
---


<h1 id="analysis">Analysis</h1>

<p>The charts below are simple aggregate analyses of Government of Canada contract spending data. They are generated from the <a href="/data">combined dataset</a> of scraped Proactive Disclosure websites and Open Government CSV data. See the <a href="/methodology">Methodology</a> page to learn more about how this data was aggregated and analyzed.</p>

<p>Because the spending data represented here was normalized year-by-year (to the closest calendar year), given the <a href="/methodology#limitations">limitations of the source data</a>, the numbers below should be considered as estimated rather than actual values. Total spending amounts on a per-company and per-year basis are not published at a government-wide level by the Government of Canada.</p>

<p>This is a volunteer-led effort, and no guarantees are made for the accuracy of the data or the processes used to aggregate and analyze it. You can <a href="/data">download the full dataset</a> yourself to conduct more advanced analyses of it.</p>

<h2 id="government-wide-aggregate-data">Government-wide aggregate data</h2>

<h3 id="total-government-wide-contract-spending">Total government-wide contract spending</h3>

<p>This chart lists the total number of contract and amendment entries included in the combined dataset, by fiscal year, for the government as a whole:</p>

{!! \App\ChartOps::run('general/entries-by-year', 'entriesByYearOverall', [], 'arrayToChartJsStackedTranspose', [
  'useConfigYears' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_year',
  'colorMapping' => 'keyword',
]) !!}

<p>This chart lists the same data as above, but by fiscal quarter:</p>

{!! \App\ChartOps::run('general/entries-by-fiscal', 'entriesByFiscalOverall', [], 'arrayToChartJsStackedTranspose', [
  'useConfigFiscal' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_fiscal',
  'colorMapping' => 'keyword',
]) !!}

<p>This chart represents the total effective value of government contracts included in the combined dataset, by calendar year, for the government as a whole:</p>

{!! \App\ChartOps::run('general/effective-overall-total-by-year-' . $config['startYear'] . '-to-' . $config['endYear'], 'effectiveOverallTotalByYear', [], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'timeColumn' => 'effective_year',
  'valueColumn' => 'sum_yearly_value',
  'isSingleEntry' => 1,
  'singleEntryLabel' => 'Total value',
  'chartOptions' => 'timeStackedCurrency',
]) !!}

<h3 id="total-contract-spending-by-department">Total contract spending by department</h3>

<p>This chart lists the total number of contract and amendment entries included in the combined dataset, by department, by fiscal year:</p>

<p>[entries-by-year.csv]</p>

<p>This chart lists the same data as above, but by fiscal quarter:</p>

<p>[entries-by-fiscal.csv]</p>

<p>This chart lists the total effective value of each department’s contracts, by year:</p>

<p>[effective-total-by-year-2008-to-2017.csv]</p>

<h3 id="largest-companies-by-government-wide-contract-spending">Largest companies by government-wide contract spending</h3>

<p>This table lists the largest companies by total number of contract and amendment entries in the combined dataset, from 2008 to 2017:</p>

<p>[largest-companies-by-entries-total-2008-to-2017.csv]</p>

<p>This chart lists the top 10 companies by total number of contract and amendment entries, by year:</p>

<p>[largest-companies-by-entries-by-year-2008-to-2017.csv]</p>

<p>This table lists the largest companies by total effective contract value, government-wide, from 2008 to 2017:</p>

<p>[largest-companies-by-effective-value-total-2008-to-2017.csv]</p>

<p>This chart lists the top 10 companies by total effective contract value, government-wide, by year:</p>

<p>[largest-companies-by-effective-value-by-year-2008-to-2017.csv]</p>

<h2 id="aggregate-data-by-department">Aggregate data by department</h2>

<p>[Select a department]</p>

<p>This table lists the largest companies by total number of contract and amendment entries from [department name], from 2008 to 2017:</p>

<p>[largest-companies-by-entries-total-2008-to-2017.csv]</p>

<p>This chart lists the top 10 companies by total number of contract and amendment entries from [department name], by year:</p>

<p>[largest-companies-by-entries-by-year-2008-to-2017.csv]</p>

<p>This table lists the largest companies by total effective contract value from [department name], from 2008 to 2017:</p>

<p>[largest-companies-by-effective-value-total-2008-to-2017.csv]</p>

<p>This chart lists the top 10 companies by total effective contract value from [department name], by year:</p>

<p>[largest-companies-by-effective-value-by-year-2008-to-2017.csv]</p>

<h2 id="aggregate-data-by-company-10-largest-companies">Aggregate data by company (10 largest companies)</h2>

<p>[Select a company]</p>

<p>This table lists departments by total number of contract and amendment entries with [company name], from 2008 to 2017:</p>

<p>[largest-departments-by-entries-total-2008-to-2017.csv]</p>

<p>This chart lists departments by total number of contract and amendment entries with [company name], by year:</p>

<p>[largest-departments-by-entries-by-year-2008-to-2017.csv]</p>

<p>This table lists departments by total effective contract value with [company name], from 2008 to 2017:</p>

<p>[largest-departments-by-effective-value-total-2008-to-2017.csv]</p>

<p>This chart lists departments by total effective contract value with [company name], by year:
[largest-departments-by-effective-value-by-year-2008-to-2017.csv]</p>

<h2 id="other-analyses">Other analyses</h2>

<p>You can use the <a href="/data">combined dataset</a> to conduct your own analyses of Government of Canada contracting data. See <a href="/methodology#future-improvements">future improvements</a> for reflections on potential next steps to improve this dataset and generate more detailed insights.</p>

<p>If you publish your own analyses using this data, please <a href="/contact">let us know</a>!</p>