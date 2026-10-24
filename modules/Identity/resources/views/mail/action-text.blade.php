Halo, {!! $name !!}

@foreach ($intro as $line)
{!! $line !!}

@endforeach
{!! $actionText !!}: {!! $actionUrl !!}

@foreach ($outro as $line)
{!! $line !!}
@endforeach

Salam,
Tim {!! $appName !!}
