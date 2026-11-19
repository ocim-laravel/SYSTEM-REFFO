@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Tambah Pelanggan</h1>

    @include('customers._form', [
        'action' => route('customers.store'),
        'method' => 'POST',
    ])
@endsection
