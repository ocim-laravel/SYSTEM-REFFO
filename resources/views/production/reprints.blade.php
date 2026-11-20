@extends('layouts.app')

@section('title', 'Pengajuan Cetak Ulang')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px">
            <h1 class="text-2xl font-semibold text-gray-900" style="margin:0">Pengajuan Cetak Ulang</h1>
            <a href="{{ route('production.index') }}" class="btn secondary">Kembali ke Antrean</a>
        </div>

        @if ($reprints->isEmpty())
            <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
                Belum ada pengajuan cetak ulang.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diminta Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($reprints as $rr)
                            @php($item = $rr->printJob?->orderItem)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item?->order?->code ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    @if ($item)
                                        <a href="{{ route('production.show', $item) }}" class="underline">{{ $item->title }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ number_format((float) $rr->qty, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $rr->reason }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $rr->requester?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $rr->status?->label() ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    @if ($rr->status === \App\Enums\ReprintStatus::Pending)
                                        @can('approveReprint', $rr)
                                            <form method="POST" action="{{ route('production.reprints.approve', $rr) }}" style="display:inline-block;margin-bottom:4px">
                                                @csrf
                                                <input type="number" name="actual_qty" step="0.01" min="0" placeholder="Qty aktual (opsional)" style="width:160px;margin-bottom:4px">
                                                <button type="submit" class="btn small">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('production.reprints.reject', $rr) }}" style="display:inline-block">
                                                @csrf
                                                <button type="submit" class="btn small danger">Tolak</button>
                                            </form>
                                        @else
                                            <span class="muted">Menunggu persetujuan</span>
                                        @endcan
                                    @else
                                        <span class="muted">{{ $rr->approver?->name ? 'Oleh '.$rr->approver->name : '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
