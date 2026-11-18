@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Ubah Divisi</h1>

    @include('system.divisions._form', [
        'action' => route('system.divisions.update', $division),
        'method' => 'PUT',
        'division' => $division,
    ])
@endsection
