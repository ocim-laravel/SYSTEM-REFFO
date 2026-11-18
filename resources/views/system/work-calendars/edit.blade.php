@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Ubah Entri Kalender Kerja</h1>

    @include('system.work-calendars._form', [
        'action' => route('system.work-calendars.update', $workCalendar),
        'method' => 'PUT',
        'workCalendar' => $workCalendar,
    ])
@endsection
