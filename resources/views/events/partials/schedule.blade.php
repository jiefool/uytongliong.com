{{ $event->event_date->format('l, F j, Y') }}
@if ($event->start_time)
    · {{ substr($event->start_time, 0, 5) }}@if ($event->end_time)–{{ substr($event->end_time, 0, 5) }}@endif&#32;({{ $event->timezone }})
@else
    · All day
@endif
