@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Divisi</h1>
            <p class="muted" style="margin-top:0">Kelola divisi dan status aktifnya.</p>
        </div>
        <a href="{{ route('system.divisions.create') }}" class="btn">Tambah Divisi</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Pengguna</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($divisions as $division)
                <tr>
                    <td><strong>{{ $division->code }}</strong></td>
                    <td>{{ $division->name }}</td>
                    <td>{{ $division->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>{{ $division->users_count }}</td>
                    <td>
                        <a href="{{ route('system.divisions.edit', $division) }}" class="btn small secondary">Ubah</a>
                        <form method="POST" action="{{ route('system.divisions.destroy', $division) }}" class="inline-form" onsubmit="return confirm('Hapus divisi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn small danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Belum ada divisi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">
        {{ $divisions->links() }}
    </div>
@endsection
