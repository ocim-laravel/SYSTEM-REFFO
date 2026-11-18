@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Kalender Kerja</h1>
            <p class="muted" style="margin-top:0">Kelola hari kerja dan hari libur perusahaan (dasar BR-07).</p>
        </div>
        <a href="{{ route('system.work-calendars.create') }}" class="btn">Tambah Entri</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Hari Kerja</th>
                <th>Catatan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($workCalendars as $workCalendar)
                <tr>
                    <td><strong>{{ $workCalendar->date->toDateString() }}</strong></td>
                    <td>{{ $workCalendar->is_workday ? 'Ya' : 'Tidak (libur)' }}</td>
                    <td>{{ $workCalendar->note ?? '-' }}</td>
                    <td>
                        <a href="{{ route('system.work-calendars.edit', $workCalendar) }}" class="btn small secondary">Ubah</a>
                        <form method="POST" action="{{ route('system.work-calendars.destroy', $workCalendar) }}" class="inline-form" onsubmit="return confirm('Hapus entri kalender ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn small danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">Belum ada entri kalender kerja.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">
        {{ $workCalendars->links() }}
    </div>
@endsection
