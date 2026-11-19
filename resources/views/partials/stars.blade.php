@php $r = (int) round($value ?? 0); @endphp
<span class="stars" title="{{ number_format((float) ($value ?? 0), 1) }} / 5" aria-label="{{ $r }} / 5">{{ str_repeat('★', $r) }}{{ str_repeat('☆', 5 - $r) }}</span>
