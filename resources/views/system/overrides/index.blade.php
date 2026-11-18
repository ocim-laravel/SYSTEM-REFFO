@php
    use App\Enums\OverridableRule;
@endphp

@extends('layouts.app')

@section('content')
    <div class="nav">
        <h1 style="margin:0">Riwayat Override Owner</h1>
        <a href="{{ route('system.overrides.create') }}" class="btn">Catat Override</a>
    </div>

    <p class="muted">Setiap override Owner wajib beralasan dan tercatat di audit log (BR-18).</p>

    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Aturan</th>
                <th>Target</th>
                <th>Alasan</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                @php
                    $rule = OverridableRule::tryFrom(str_after($log->action, 'override.'));
                @endphp
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $rule?->label() ?? $log->action }}</td>
                    <td>
                        @if ($log->model_type)
                            {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>{{ $log->reason }}</td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Belum ada override yang tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">{{ $logs->links() }}</div>
@endsection
