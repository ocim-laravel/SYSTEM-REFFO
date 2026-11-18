@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Tambah Entri Kalender Kerja</h1>

    @include('system.work-calendars._form', [
        'action' => route('system.work-calendars.store'),
        'method' => 'POST',
        'workCalendar' => null,
    ])
@endsection
