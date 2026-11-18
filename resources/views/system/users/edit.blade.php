@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Ubah Pengguna</h1>

    @include('system.users._form', [
        'action' => route('system.users.update', $user),
        'method' => 'PUT',
        'user' => $user,
    ])
@endsection
