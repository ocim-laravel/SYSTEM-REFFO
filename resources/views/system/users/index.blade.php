@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Pengguna</h1>
            <p class="muted" style="margin-top:0">Kelola akun pengguna, peran, dan statusnya.</p>
        </div>
        <a href="{{ route('system.users.create') }}" class="btn">Tambah Pengguna</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Divisi</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roleEnum()?->label() ?? '-' }}</td>
                    <td>{{ $user->division?->name ?? '-' }}</td>
                    <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <a href="{{ route('system.users.edit', $user) }}" class="btn small secondary">Ubah</a>
                        <a href="{{ route('system.users.edit-password', $user) }}" class="btn small secondary">Reset Password</a>
                        <form method="POST" action="{{ route('system.users.toggle-active', $user) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn small secondary">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('system.users.destroy', $user) }}" class="inline-form" onsubmit="return confirm('Hapus pengguna ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn small danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">Belum ada pengguna.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">
        {{ $users->links() }}
    </div>
@endsection
