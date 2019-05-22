<table class="table table-striped">
  <thead>
    <tr>
      @foreach(get_object_vars($input[0]) as $key => $value)
      <th scope="col" @if(in_array($key, $currencyColumns)) class="text-right" @endif>{{ \App\Helpers\Cleaners::generateLabelText($key) }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @php
      $rowNumber = 0;
    @endphp
    @foreach($input as $row)
    <tr>
      @php
      $rowIndex = 0;
      @endphp
      @foreach(get_object_vars($row) as $key => $value)
        @php 
          if(in_array($key, $currencyColumns)) {
            $value = '$ ' . number_format($value, 2);
            $rightAlign = 1;
          }
          else {
            $rightAlign = 0;
          }
        @endphp
        @if($rowIndex++ == 0)
          <th scope="row" @if($rightAlign) class="text-right" @endif>{{ $value }}</th>
        @else
          <td @if($rightAlign) class="text-right" @endif>{{ $value }}</td>
        @endif
      @endforeach
    </tr>
      @php 
        $rowNumber++;
        if($rowNumber >= $limitRows) {
          break;
        }
      @endphp
    @endforeach
  </tbody>
</table>
