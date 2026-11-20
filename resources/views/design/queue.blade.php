@extends('layouts.app')

@section('title', 'Antrean Desain')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-semibold text-gray-900 mb-4">Antrean Desain</h1>

        @if ($items->isEmpty())
            <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
                Belum ada item dalam antrean desain.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->order?->code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->order?->customer?->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->category?->value ?? $item->category }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->priority?->label() ?? $item->priority }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $item->deadline_at ? $item->deadline_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->status?->label() ?? $item->status }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    @can('claim', $item)
                                        <form method="POST" action="{{ route('design.claim', $item) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                                                Ambil Tugas
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection