@extends('layouts.app')

@section('title', 'Pengiriman & Pemasangan')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
            <h1 class="text-2xl font-semibold text-gray-900" style="margin:0">Pengiriman &amp; Pemasangan</h1>
            @can('deliveries.manage')
                <a href="{{ route('deliveries.create') }}" class="btn small">Buat Jadwal</a>
            @endcan
        </div>

        <div style="margin-bottom:12px">
            @foreach (['' => 'Semua', 'today' => 'Hari Ini', 'upcoming' => 'Mendatang', 'completed' => 'Selesai'] as $key => $label)
                <a href="{{ route('deliveries.index', $key !== '' ? ['filter' => $key] : []) }}"
                   class="btn small {{ $filter === $key ? '' : 'secondary' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if ($deliveries->isEmpty())
            <div class="bg-white shadow rounded-lg p-4 text-center text-gray-500">
                Belum ada jadwal pekerjaan lapangan.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Petugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($deliveries as $delivery)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $delivery->order?->code ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $delivery->order?->customer?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $delivery->type?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $delivery->scheduled_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $delivery->crewMembers()->pluck('name')->implode(', ') ?: 'Belum ditugaskan' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $delivery->status?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">Rp {{ number_format((float) $delivery->fee, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="btn small secondary">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px">{{ $deliveries->links() }}</div>
        @endif
    </div>
@endsection
