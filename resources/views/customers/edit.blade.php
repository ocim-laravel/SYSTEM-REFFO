@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Ubah Pelanggan: {{ $customer->name }}</h1>

    @include('customers._form', [
        'action' => route('customers.update', $customer),
        'method' => 'PUT',
        'customer' => $customer,
    ])
@endsection
