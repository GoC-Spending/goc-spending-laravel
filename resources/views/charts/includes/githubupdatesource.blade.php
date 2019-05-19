<p class="text-right mb-5 ml-5">View source data: 
@php 
$index = 0;
@endphp
@foreach($links as $link)
<a href="#" class="{{ $class }}" data-url-prefix="{{ $link['urlPrefix'] }}" data-url-suffix="{{ $link['urlSuffix'] }}" data-label-prefix="{{ $link['labelPrefix'] }}" data-label-suffix="{{ $link['labelSuffix'] }}">[Label]</a>@if(++$index < count($links))
, 
@endif
@endforeach
</p>
