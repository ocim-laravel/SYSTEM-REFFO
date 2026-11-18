@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Tambah Pengguna</h1>

    @include('system.users._form', [
        'action' => route('system.users.store'),
        'method' => 'POST',
        'user' => null,
    ])
@endsection
