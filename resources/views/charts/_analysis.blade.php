---
title: 'Analysis'
description: "Aggregate statistical analyses of Government of Canada contract spending at a government-wide level."
date: 2019-05-04
draft: false
menu: 
  main:
    parent: 'analysis'
---

@php  
$i = 0 
@endphp
<h1 id="analysis">Analysis</h1>

<p>The charts below are simple aggregate analyses of Government of Canada contract spending data. They are generated from the <a href="/download/">combined dataset</a> of scraped Proactive Disclosure websites and Open Government CSV data. See the <a href="/methodology/">Methodology</a> page to learn more about how this data was aggregated and analyzed.</p>

<p>Because the spending data represented here was normalized year-by-year (to the closest calendar year), given the <a href="/methodology/#limitations">limitations of the source data</a>, the numbers below should be considered as estimated rather than actual values. Total spending amounts on a per-company and per-year basis are not published at a government-wide level by the Government of Canada.</p>

<p>This is a volunteer-led effort, and no guarantees are made for the accuracy of the data or the processes used to aggregate and analyze it. You can <a href="/download/">download the full dataset</a> yourself to conduct more advanced analyses of it.</p>

<h2 id="government-wide-aggregate-data">Government-wide aggregate data</h2>

<h3 id="total-government-wide-contract-and-amendment-entries">Total government-wide contract and amendment entries</h3>

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the total number of contract and amendment entries included in the combined dataset, by fiscal year, for the government as a whole:</p>

{!! \App\ChartOps::run('entries-by-year', 'entriesByYearOverall', [], [], 'arrayToChartJsStackedTranspose', [
  'useConfigYears' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_year',
  'colorMapping' => 'keyword',
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/entries-by-year.csv' => 'Overall contract and amendment entries, by year'
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the same data as above, but by fiscal quarter:</p>

{!! \App\ChartOps::run('entries-by-fiscal', 'entriesByFiscalOverall', [], [], 'arrayToChartJsStackedTranspose', [
  'useConfigFiscal' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_fiscal',
  'colorMapping' => 'keyword',
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/entries-by-fiscal.csv' => 'Overall contract and amendment entries, by fiscal quarter'
]])

<h3 id="total-government-wide-contract-spending">Total government-wide contract spending</h3>

<p>@include('charts.includes.target', ['i' => ++$i])This chart represents the total effective value of government contracts included in the combined dataset, by calendar year, for the government as a whole:</p>

{!! \App\ChartOps::run('effective-overall-total-by-year', 'effectiveOverallTotalByYear', [], [
    'currencyColumns' => [
      'sum_yearly_value',
    ]
  ], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'timeColumn' => 'effective_year',
  'valueColumn' => 'sum_yearly_value',
  'isSingleEntry' => 1,
  'singleEntryLabel' => 'Total value',
  'chartOptions' => 'timeStackedCurrency',
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/effective-overall-total-by-year-2008-to-2017.csv' => 'Overall effective government-wide contract spending'
], 'marginBottomClass' => 'mb-2'])

<div class="alert alert-warning mb-5">
  <p>This chart appears to show that total contracting spending increases year over year. But this might be <a href="/methodology/#producing-aggregate-analysis-trends">due to issues with the historical data</a>, rather than an actual trend. Be careful in interpreting any year-over-year trends displayed on this page.</p>

  <p class="mb-0"><a href="https://www.canada.ca/en/treasury-board-secretariat/corporate/reports/contracting-data.html">Check the Purchasing Activity Reports</a> for historical whole-of-government data on contracting totals until 2016. For example, <a href="https://www.canada.ca/en/treasury-board-secretariat/corporate/reports/contracting-data/2013-purchasing-activity-report.html">the 2013 report</a> shows total spending of $14.6 billion (including contracts below our $10,000 proactive disclosure threshold).</p>
</div>

<h3 id="total-contract-and-amendment-entries-by-department">Total contract and amendment entries by department</h3>

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the total number of contract and amendment entries included in the combined dataset, by department, by fiscal year:</p>

{!! \App\ChartOps::run('entries-by-department-by-year', 'entriesByYear', [], [], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'valueColumn' => 'total_entries',
  'labelColumn' => 'owner_acronym',
  'timeColumn' => 'source_year',
  
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/entries-by-department-by-year.csv' => 'Contract and amendment entries by department, by fiscal year'
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the same data as above, but by fiscal quarter:</p>

{!! \App\ChartOps::run('entries-by-department-by-fiscal', 'entriesByFiscal', [], [], 'arrayToChartJsStacked', [
  'useConfigFiscal' => 1,
  'valueColumn' => 'total_entries',
  'labelColumn' => 'owner_acronym',
  'timeColumn' => 'source_fiscal',
  
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/entries-by-department-by-fiscal.csv' => 'Contract and amendment entries by department, by fiscal quarter'
]])

<h3 id="total-contract-spending-by-department">Total contract spending by department</h3>

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the total effective value of each department’s contracts, by year:</p>

{!! \App\ChartOps::run('effective-total-by-year', 'effectiveTotalByYear', [], [
    'currencyColumns' => [
      'sum_yearly_value',
    ]
  ], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'timeColumn' => 'effective_year',
  'labelColumn' => 'owner_acronym',
  'valueColumn' => 'sum_yearly_value',
  'chartOptions' => 'timeStackedCurrency',
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/effective-total-by-year-2008-to-2017.csv' => 'Effective total contract spending by department, by year'
]])

<h3 id="largest-companies-by-government-wide-contract-spending">Largest companies by government-wide contract spending</h3>

<p>@include('charts.includes.target', ['i' => ++$i])This table lists the largest companies by total number of contract and amendment entries in the combined dataset, from 2008 to 2017:</p>

{!! \App\ChartOps::run('largest-companies-by-effective-value-total', 'largestCompaniesByEntries', [], [], 'arrayToTable', [
  'limitRows' => 10,
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/largest-companies-by-entries-total-2008-to-2017.csv' => 'Top 100 companies by number of contract and amendment entries'
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the top 10 companies by total number of contract and amendment entries, government-wide, by year:</p>

{!! \App\ChartOps::run('largest-companies-by-entries-by-year', 'largestCompaniesByEntriesByYear', [], [], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'valueColumn' => 'total_entries',
  'labelColumn' => 'gen_vendor_normalized',
  'timeColumn' => 'source_year',
  
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/largest-companies-by-entries-by-year-2008-to-2017.csv' => 'Top 10 companies by number of contract and amendment entries, by year'
]])

<p>@include('charts.includes.target', ['i' => ++$i])This table lists the largest companies by total effective contract value, government-wide, from 2008 to 2017:</p>

{!! \App\ChartOps::run('largest-companies-by-effective-value-total', 'largestCompaniesByEffectiveValue', [], [
    'currencyColumns' => [
      'sum_effective_value',
    ]
  ], 'arrayToTable', [
  'currencyColumns' => ['sum_effective_value'],
  'limitRows' => 10,
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/largest-companies-by-effective-value-total-2008-to-2017.csv' => 'Top 100 companies by effective contract value'
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the top 10 companies by total effective contract value, government-wide, by year:</p>

{!! \App\ChartOps::run('largest-companies-by-effective-value-by-year', 'largestCompaniesByEffectiveValueByYear', [], [
    'currencyColumns' => [
      'sum_yearly_value',
    ]
  ], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'timeColumn' => 'effective_year',
  'labelColumn' => 'vendor_normalized',
  'valueColumn' => 'sum_yearly_value',
  'chartOptions' => 'timeStackedCurrency',
]) !!}

@include('charts.includes.githubsource', ['links' => [
  'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/general/largest-companies-by-effective-value-by-year-2008-to-2017.csv' => 'Top 10 companies by effective contract value, by year'
], 'marginBottomClass' => 'mb-2'])

<div class="alert alert-warning mb-5">
  <p class="mb-0">This chart may show negative values for a given year, in cases where later amendments reduced the overall value of a large contract compared to earlier entries. See the <a href="/methodology/#normalizing-by-year">Normalizing by year</a> methodology section for more details.</p>
</div>

<h2 id="aggregate-data-by-department">Aggregate data by department</h2>

<label for="owner-select">Select a department</label>
<select id="owner-select" name="owner-select" class="custom-select mb-3">
@foreach (\App\AnalysisOps::allOwnerAcronyms() as $ownerAcronym)

<option value="{{ $ownerAcronym }}" data-url-target="{{ $ownerAcronym }}" data-chart-array="{{ json_encode(\App\ChartOps::multiRun([
  [
    'id' => 'department-largest-companies-by-entries-by-year',
    'dataMethod' => 'largestCompaniesByEntriesByYear',
    'dataMethodParams' => $ownerAcronym,
    'postProcessingParams' => [],
    'chartMethod' => 'arrayToChartJsStacked',
    'chartMethodParams' => [
      'useConfigYears' => 1,
      'timeColumn' => 'source_year',
      'labelColumn' => 'gen_vendor_normalized',
      'valueColumn' => 'total_entries',
      'generatorMethod' => 'generatePlainArray',
    ],
  ],

  [
    'id' => 'department-entries-above-and-below-25k-by-year',
    'dataMethod' => 'entriesAboveAndBelow25kByYearByOwner',
    'dataMethodParams' => $ownerAcronym,
    'postProcessingParams' => [
      'currencyColumns' => [
        'original_sum_below_25k',
        'original_sum_above_25k',
      ]
    ],
    'chartMethod' => 'arrayToChartJsStackedTranspose',
    'chartMethodParams' => [
      'useConfigYears' => 1,
      'timeColumn' => 'source_year',
      'valueColumns' => ['entries_below_25k', 'entries_above_25k'],
      'colorMapping' => 'keyword',
      'generatorMethod' => 'generatePlainArray',
    ],
  ],

  [
    'id' => 'department-largest-companies-by-effective-value-by-year',
    'dataMethod' => 'largestCompaniesByEffectiveValueByYear',
    'dataMethodParams' => $ownerAcronym,
    'postProcessingParams' => [
      'currencyColumns' => [
        'sum_yearly_value',
      ]
    ],
    'chartMethod' => 'arrayToChartJsStacked',
    'chartMethodParams' => [
      'useConfigYears' => 1,
      'timeColumn' => 'effective_year',
      'labelColumn' => 'vendor_normalized',
      'valueColumn' => 'sum_yearly_value',
      'chartOptions' => 'timeStackedCurrency',
      'generatorMethod' => 'generatePlainArray',
    ],
  ],

])) }}">{{ \App\Helpers\Cleaners::generateLabelText($ownerAcronym) }}</option>

@endforeach
</select>

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the number of initial contract entries from <span class="update-owner">[department name]</span> that are below or above $25k, by year:</p>

<canvas id="department-entries-above-and-below-25k-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

@include('charts.includes.githubupdatesource', ['class' => 'owner-select-link', 'links' => [
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/departments/',
    'urlSuffix' => '/entries-above-and-below-25k-by-year-2008-to-2017.csv',
    'labelPrefix' => 'Entries above and below $25k for ',
    'labelSuffix' => '',
  ]
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the top 10 companies by total number of contract and amendment entries from <span class="update-owner">[department name]</span>, by year:</p>

<canvas id="department-largest-companies-by-entries-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

@include('charts.includes.githubupdatesource', ['class' => 'owner-select-link', 'links' => [
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/departments/',
    'urlSuffix' => '/largest-companies-by-effective-value-total-2008-to-2017.csv',
    'labelPrefix' => '',
    'labelSuffix' => '’s top 100 companies by number of contract and amendment entries',
  ],
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/departments/',
    'urlSuffix' => '/largest-companies-by-effective-value-by-year-2008-to-2017.csv',
    'labelPrefix' => '',
    'labelSuffix' => '’s top 10 companies by number of contract and amendment entries, by year',
  ],
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists the top 10 companies by total effective contract value from <span class="update-owner">[department name]</span>, by year:</p>

<canvas id="department-largest-companies-by-effective-value-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

@include('charts.includes.githubupdatesource', ['class' => 'owner-select-link', 'links' => [
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/departments/',
    'urlSuffix' => '/largest-companies-by-effective-value-total-2008-to-2017.csv',
    'labelPrefix' => '',
    'labelSuffix' => '’s top 100 companies by effective value',
  ],
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/departments/',
    'urlSuffix' => '/largest-companies-by-effective-value-by-year-2008-to-2017.csv',
    'labelPrefix' => '',
    'labelSuffix' => '’s top 10 companies by effective value, by year',
  ],
]])

<h2 id="aggregate-data-by-company">Aggregate data by company (200 largest companies)</h2>

<label for="vendor-select">Select a company</label>
<select id="vendor-select" name="vendor-select" class="custom-select mb-3">
@foreach (\App\AnalysisOps::largestVendorNamesByEffectiveValue(200, 1) as $vendor)

<option value="{{ $vendor }}" data-url-target="{{ \Illuminate\Support\Str::slug($vendor) }}" data-chart-array="{{ json_encode(\App\ChartOps::multiRun([
  [
    'id' => 'vendor-largest-departments-by-entries-by-year',
    'dataMethod' => 'largestDepartmentsByEntriesByYear',
    'dataMethodParams' => $vendor,
    'postProcessingParams' => [],
    'chartMethod' => 'arrayToChartJsStacked',
    'chartMethodParams' => [
      'useConfigYears' => 1,
      'timeColumn' => 'source_year',
      'labelColumn' => 'owner_acronym',
      'valueColumn' => 'total_entries',
      'generatorMethod' => 'generatePlainArray',
    ],
  ],

  [
    'id' => 'vendor-largest-departments-by-effective-value-by-year',
    'dataMethod' => 'largestDepartmentsByEffectiveValueByYear',
    'dataMethodParams' => $vendor,
    'postProcessingParams' => [
      'currencyColumns' => [
        'sum_yearly_value',
      ]
    ],
    'chartMethod' => 'arrayToChartJsStacked',
    'chartMethodParams' => [
      'useConfigYears' => 1,
      'timeColumn' => 'effective_year',
      'labelColumn' => 'owner_acronym',
      'valueColumn' => 'sum_yearly_value',
      'chartOptions' => 'timeStackedCurrency',
      'generatorMethod' => 'generatePlainArray',
    ],
  ],

])) }}">{{ $vendor }}</option>

@endforeach
</select>

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists departments by total number of contract and amendment entries with <span class="update-vendor">[company name]</span>, by year:</p>

<canvas id="vendor-largest-departments-by-entries-by-year" width="400" height="200" class="vendor-select-canvas"></canvas>

@include('charts.includes.githubupdatesource', ['class' => 'vendor-select-link', 'links' => [
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/vendors/',
    'urlSuffix' => '/largest-departments-by-entries-total-2008-to-2017.csv',
    'labelPrefix' => 'Top departments for ',
    'labelSuffix' => ' by number of contract and amendment entries',
  ],
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/vendors/',
    'urlSuffix' => '/largest-departments-by-entries-by-year-2008-to-2017.csv',
    'labelPrefix' => 'Top 10 departments for ',
    'labelSuffix' => ' by number of contract and amendment entries, by year',
  ],
]])

<p>@include('charts.includes.target', ['i' => ++$i])This chart lists departments by total effective contract value with <span class="update-vendor">[company name]</span>, from 2008 to 2017:</p>

<canvas id="vendor-largest-departments-by-effective-value-by-year" width="400" height="200" class="vendor-select-canvas"></canvas>

@include('charts.includes.githubupdatesource', ['class' => 'vendor-select-link', 'links' => [
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/vendors/',
    'urlSuffix' => '/largest-departments-by-effective-value-total-2008-to-2017.csv',
    'labelPrefix' => 'Top departments for ',
    'labelSuffix' => ' by effective contract value',
  ],
  [
    'urlPrefix' => 'https://github.com/GoC-Spending/goc-spending-analysis/blob/master/vendors/',
    'urlSuffix' => '/largest-departments-by-effective-value-by-year-2008-to-2017.csv',
    'labelPrefix' => 'Top 10 departments for ',
    'labelSuffix' => ' by effective contract value, by year',
  ],
]])

<h2 id="other-analyses">Other analyses</h2>

<p>You can use the <a href="/download/">combined dataset</a> to conduct your own analyses of Government of Canada contracting data. See <a href="/methodology/#future-improvements">future improvements</a> for reflections on potential next steps to improve this dataset and generate more detailed insights.</p>

<p>If you publish your own analyses using this data, please <a href="/about/#get-in-touch">let us know</a>!</p>
