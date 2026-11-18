@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Notifikasi</h1>
    <p class="muted">Daftar notifikasi untuk akun Anda.</p>

    <form method="POST" action="{{ route('notifications.read-all') }}" class="inline-form" style="margin-bottom:14px">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn small secondary">Tandai semua dibaca</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Isi</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($notifications as $item)
                <tr>
                    <td><strong>{{ $item->title }}</strong></td>
                    <td>{{ $item->body ?? '-' }}</td>
                    <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $item->read_at === null ? 'Belum dibaca' : 'Dibaca' }}</td>
                    <td>
                        @if ($item->read_at === null)
                            <form method="POST" action="{{ route('notifications.update', $item) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn small secondary">Tandai dibaca</button>
                            </form>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Belum ada notifikasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
