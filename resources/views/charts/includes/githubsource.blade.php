@php 
if(! isset($marginBottomClass)) {
  $marginBottomClass = 'mb-5';
}
@endphp 
<p class="text-right {{ $marginBottomClass }} ml-5">View source data: 
@php 
$index = 0;
@endphp
@foreach($links as $url => $label)
<a href="{{ $url }}">{{ $label }}</a>@if(++$index < count($links))
, 
@endif
@endforeach
</p>
