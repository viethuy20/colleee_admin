Duplication occurred in point redemption.

Datetime: 
{{ $datetime }}

Duplicate count.
@foreach ($message_list as $key => $count)
・{{ $key }}: {{ $count }}
@endforeach