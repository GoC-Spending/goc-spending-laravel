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

{!! \App\ChartOps::run('general/entries-by-year', 'entriesByYearOverall', [], [], 'arrayToChartJsStackedTranspose', [
  'useConfigYears' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_year',
  'colorMapping' => 'keyword',
]) !!}

<p>This chart lists the same data as above, but by fiscal quarter:</p>

{!! \App\ChartOps::run('general/entries-by-fiscal', 'entriesByFiscalOverall', [], [], 'arrayToChartJsStackedTranspose', [
  'useConfigFiscal' => 1,
  'valueColumns' => ['total_contracts', 'total_amendments'],
  'timeColumn' => 'source_fiscal',
  'colorMapping' => 'keyword',
]) !!}

<p>This chart represents the total effective value of government contracts included in the combined dataset, by calendar year, for the government as a whole:</p>

{!! \App\ChartOps::run('general/effective-overall-total-by-year-' . $config['startYear'] . '-to-' . $config['endYear'], 'effectiveOverallTotalByYear', [], [], 'arrayToChartJsStacked', [
  'useConfigYears' => 1,
  'timeColumn' => 'effective_year',
  'valueColumn' => 'sum_yearly_value',
  'isSingleEntry' => 1,
  'singleEntryLabel' => 'Total value',
  'chartOptions' => 'timeStackedCurrency',
]) !!}

<h3 id="total-contract-and-amendment-entries-by-department">Total contract and amendment entries by department</h3>

<p>This chart lists the total number of contract and amendment entries included in the combined dataset, by department, by fiscal year:</p>

<p>[entries-by-year.csv]</p>

<p>This chart lists the same data as above, but by fiscal quarter:</p>

<p>[entries-by-fiscal.csv]</p>

<h3 id="total-contract-spending-by-department">Total contract spending by department</h3>

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

<label for="owner-select">Select a department</label>
<select id="owner-select" name="owner-select" class="custom-select mb-3">
@foreach (\App\AnalysisOps::allOwnerAcronyms() as $ownerAcronym)

<option value="{{ $ownerAcronym }}" data-chart-array="{{ json_encode(\App\ChartOps::multiRun([
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

<p>This chart lists the number of initial contract entries from <span class="update-owner">[department name]</span> that are below or above $25k, by year:</p>

<canvas id="department-entries-above-and-below-25k-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

<p>This chart lists the top 10 companies by total number of contract and amendment entries from <span class="update-owner">[department name]</span>, by year:</p>

<canvas id="department-largest-companies-by-entries-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

<p>This chart lists the top 10 companies by total effective contract value from <span class="update-owner">[department name]</span>, by year:</p>

<canvas id="department-largest-companies-by-effective-value-by-year" width="400" height="200" class="owner-select-canvas"></canvas>

<h2 id="aggregate-data-by-company-10-largest-companies">Aggregate data by company (10 largest companies)</h2>

<label for="vendor-select">Select a company</label>
<select id="vendor-select" name="vendor-select" class="custom-select mb-3">
@foreach (\App\AnalysisOps::largestVendorNamesByEffectiveValue() as $vendor)

<option value="{{ $vendor }}" data-chart-array="{{ json_encode(\App\ChartOps::multiRun([
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

<p>This chart lists departments by total number of contract and amendment entries with <span class="update-vendor">[company name]</span>, by year:</p>

<canvas id="vendor-largest-departments-by-entries-by-year" width="400" height="200" class="vendor-select-canvas"></canvas>

<p>This table lists departments by total effective contract value with <span class="update-vendor">[company name]</span>, from 2008 to 2017:</p>

<canvas id="vendor-largest-departments-by-effective-value-by-year" width="400" height="200" class="vendor-select-canvas"></canvas>

<h2 id="other-analyses">Other analyses</h2>

<p>You can use the <a href="/data">combined dataset</a> to conduct your own analyses of Government of Canada contracting data. See <a href="/methodology#future-improvements">future improvements</a> for reflections on potential next steps to improve this dataset and generate more detailed insights.</p>

<p>If you publish your own analyses using this data, please <a href="/contact">let us know</a>!</p>