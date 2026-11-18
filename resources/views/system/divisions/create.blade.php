@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Tambah Divisi</h1>

    @include('system.divisions._form', [
        'action' => route('system.divisions.store'),
        'method' => 'POST',
        'division' => null,
    ])
@endsection
