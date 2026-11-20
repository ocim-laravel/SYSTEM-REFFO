@extends('layouts.app')

@section('title', 'Antrean Produksi')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px">
            <h1 class="text-2xl font-semibold text-gray-900" style="margin:0">Antrean Produksi</h1>
            <a href="{{ route('production.reprints.index') }}" class="btn secondary">Pengajuan Cetak Ulang</a>
        </div>

        @foreach ([
            ['title' => 'Urgent', 'items' => $urgent, 'class' => 'danger'],
            ['title' => 'H-1', 'items' => $h1, 'class' => 'warn'],
            ['title' => 'Reguler', 'items' => $regular, 'class' => ''],
        ] as $section)
            <h2 style="margin:20px 0 8px">{{ $section['title'] }}</h2>
            @if ($section['items']->isEmpty())
                <div class="bg-white shadow rounded-lg p-4 text-center text-gray-500" style="margin-bottom:12px">
                    Belum ada pekerjaan di antrean {{ strtolower($section['title']) }}.
                </div>
            @else
                <div class="bg-white shadow rounded-lg overflow-hidden" style="margin-bottom:12px">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penanggung Jawab</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($section['items'] as $job)
                                @php($item = $job->orderItem)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->order?->code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->order?->customer?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->title ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->priority?->label() ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->status?->label() ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $job->assignee?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <a href="{{ route('production.show', $item) }}" class="btn small secondary">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>
@endsection
