@extends('layouts.app')

@section('title', 'Antrean Finishing')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-semibold text-gray-900" style="margin:0 0 16px">Antrean Finishing</h1>

        @if ($items->isEmpty())
            <div class="bg-white shadow rounded-lg p-4 text-center text-gray-500">
                Belum ada item dalam antrean finishing.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
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
                        @foreach ($items as $item)
                            @php($job = $item->finishingJobs->sortByDesc('id')->first())
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->order?->code ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->order?->customer?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->priority?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->status?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $job?->assignee?->name ?? 'Belum ditugaskan' }}
                                    @if ($job)
                                        <div class="muted">{{ $job->status?->label() }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <a href="{{ route('finishing.show', $item) }}" class="btn small secondary">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
