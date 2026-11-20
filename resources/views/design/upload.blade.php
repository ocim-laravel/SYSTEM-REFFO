@extends('layouts.app')

@section('title', 'Unggah Desain')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-semibold text-gray-900 mb-2">Unggah Desain</h1>
        <p class="text-sm text-gray-600 mb-4">
            Item: <strong>{{ $item->title }}</strong>
            @if ($item->order)
                ({{ $item->order->code }})
            @endif
        </p>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('revision'))
            <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                {{ $errors->first('revision') }}
            </div>
        @endif

        @if (($job->revision_cycle_used ?? 0) >= 3)
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                Siklus revisi habis. Ajukan Change Request.
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Riwayat Lengkap Desain</h2>
            @if ($history->isEmpty())
                <p class="text-gray-500 text-sm">Belum ada riwayat desain.</p>
            @else
                <ol class="space-y-3">
                    @foreach ($history as $event)
                        <li class="border-l-4 border-gray-200 pl-3">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $event['label'] }}
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                    {{ $event['type'] === 'file' ? 'Versi' : ($event['type'] === 'revision' ? 'Revisi' : 'ACC') }}
                                </span>
                            </p>
                            @if ($event['detail'])
                                <p class="text-sm text-gray-700">{{ $event['detail'] }}</p>
                            @endif
                            <p class="text-xs text-gray-500">
                                {{ $event['actor'] }}
                                @if ($event['happened_at'])
                                    · {{ $event['happened_at']->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Change Request</h2>
            @if ($changeRequests->isEmpty())
                <p class="text-gray-500 text-sm mb-4">Belum ada Change Request.</p>
            @else
                <ul class="space-y-3 mb-4">
                    @foreach ($changeRequests as $changeRequest)
                        <li class="border-l-4 border-amber-200 pl-3">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $changeRequest->status?->label() ?? $changeRequest->status }}
                            </p>
                            <p class="text-sm text-gray-700">{{ $changeRequest->reason }}</p>
                            <p class="text-xs text-gray-500">
                                Dampak biaya: {{ number_format((float) $changeRequest->cost_impact, 2, ',', '.') }}
                                @if ($changeRequest->deadline_impact)
                                    · Dampak deadline: {{ $changeRequest->deadline_impact->format('d/m/Y H:i') }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                @if ($changeRequest->approved_by)
                                    Disetujui/Ditolak oleh: {{ $changeRequest->approvedBy?->name ?? '-' }}
                                    @if ($changeRequest->approved_at)
                                        · {{ $changeRequest->approved_at->format('d/m/Y H:i') }}
                                    @endif
                                @else
                                    Menunggu persetujuan
                                @endif
                            </p>
                            @can('approve', $changeRequest)
                                @if ($changeRequest->status === \App\Enums\ChangeRequestStatus::Pending)
                                    <div class="mt-2 flex gap-2">
                                        <form method="POST" action="{{ route('design.change_request.approve', $changeRequest) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                Setujui
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('design.change_request.reject', $changeRequest) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif

            @can('changeRequest', $item)
                @if ($item->status === \App\Enums\ItemStatus::Acc)
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Ajukan Change Request</h3>
                    <form method="POST" action="{{ route('design.change_request', $item) }}">
                        @csrf
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
                            <textarea name="reason" id="reason" rows="3" class="block w-full text-sm border-gray-300 rounded-md" required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="cost_impact" class="block text-sm font-medium text-gray-700 mb-1">Dampak biaya</label>
                            <input type="number" step="0.01" min="0" name="cost_impact" id="cost_impact" value="{{ old('cost_impact', '0') }}" class="block w-full text-sm border-gray-300 rounded-md" required>
                            @error('cost_impact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="deadline_impact" class="block text-sm font-medium text-gray-700 mb-1">Dampak deadline (opsional)</label>
                            <input type="datetime-local" name="deadline_impact" id="deadline_impact" value="{{ old('deadline_impact') }}" class="block w-full text-sm border-gray-300 rounded-md">
                            @error('deadline_impact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="cr_design_file_id" class="block text-sm font-medium text-gray-700 mb-1">File terkait (opsional)</label>
                            <select name="design_file_id" id="cr_design_file_id" class="block w-full text-sm border-gray-300 rounded-md">
                                <option value="">-</option>
                                @foreach ($files as $file)
                                    <option value="{{ $file->id }}" @selected((string) old('design_file_id') === (string) $file->id)>v{{ $file->version }} ({{ $file->kind?->label() }})</option>
                                @endforeach
                            </select>
                            @error('design_file_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white text-sm rounded hover:bg-amber-700">
                            Ajukan Change Request
                        </button>
                    </form>
                @endif
            @endcan
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Versi File</h2>
            @if ($files->isEmpty())
                <p class="text-gray-500 text-sm">Belum ada file yang diunggah.</p>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Versi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Diunggah</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Oleh</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($files as $file)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">v{{ $file->version }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    {{ $file->kind?->label() ?? $file->kind }}
                                    @if ($file->kind === \App\Enums\DesignFileKind::Final)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Final</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $file->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $file->uploader?->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <a href="{{ route('design.file.download', $file) }}" class="text-indigo-600 hover:underline">Unduh</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Riwayat ACC</h2>
            @if ($approvals->isEmpty())
                <p class="text-gray-500 text-sm">Belum ada ACC.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($approvals as $approval)
                        <li class="border-l-4 border-green-200 pl-3">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $approval->designFile?->version ? 'v'.$approval->designFile->version : 'File' }}
                                @if ($approval->designFile?->kind === \App\Enums\DesignFileKind::Final)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Final</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $approval->approver?->name ?? '-' }}
                                @if ($approval->approved_at)
                                    · {{ $approval->approved_at->format('d/m/Y H:i') }}
                                @endif
                                · {{ $approval->method }}
                            </p>
                            @if ($approval->note)
                                <p class="text-sm text-gray-700 mt-1">{{ $approval->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Riwayat Revisi</h2>
            @if ($revisions->isEmpty())
                <p class="text-gray-500 text-sm">Belum ada revisi yang diminta.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($revisions as $revision)
                        <li class="border-l-4 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-900">Siklus ke-{{ $revision->cycle_no }}</p>
                            <p class="text-sm text-gray-700">{{ $revision->request_note }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $revision->requester?->name ?? '-' }}
                                @if ($revision->requested_at)
                                    · {{ $revision->requested_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @can('designs.revise')
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-3">Minta Revisi</h2>
                @if (($job->revision_cycle_used ?? 0) >= 3)
                    <p class="text-sm text-gray-500 mb-3">Siklus revisi gratis (3) habis. Ajukan Change Request.</p>
                @else
                    <form method="POST" action="{{ route('design.revise', $item) }}">
                        @csrf
                        <div class="mb-4">
                            <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Catatan revisi</label>
                            <textarea name="note" id="note" rows="3" class="block w-full text-sm border-gray-300 rounded-md" required></textarea>
                            @error('note')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('request_note')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                            Minta Revisi
                        </button>
                    </form>
                @endif
            </div>
        @endcan

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Unggah Versi Baru</h2>
            <form method="POST" action="{{ route('design.upload', $item) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">File (PDF/JPG/PNG, maks 10 MB)</label>
                    <input type="file" name="file" id="file" class="block w-full text-sm text-gray-900" required>
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('item')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                    Unggah
                </button>
            </form>
        </div>

        @can('submit', $item)
            @if (in_array($item->status->value, ['designing', 'revision'], true))
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-3">Ajukan ACC</h2>
                    <form method="POST" action="{{ route('design.submit', $item) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                            Ajukan ACC
                        </button>
                    </form>
                </div>
            @endif
        @endcan

        @can('approve', $item)
            @if ($item->status->value === 'awaiting_acc')
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-3">ACC Desain</h2>
                    <form method="POST" action="{{ route('design.approve', $item) }}">
                        @csrf
                        <div class="mb-4">
                            <label for="design_file_id" class="block text-sm font-medium text-gray-700 mb-1">File yang di-ACC</label>
                            <select name="design_file_id" id="design_file_id" class="block w-full text-sm border-gray-300 rounded-md">
                                @foreach ($files->sortByDesc('version') as $file)
                                    <option value="{{ $file->id }}" @selected($loop->first)>v{{ $file->version }} ({{ $file->kind?->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="method" class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                            <select name="method" id="method" class="block w-full text-sm border-gray-300 rounded-md">
                                <option value="internal">Internal</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="approval_note" class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                            <textarea name="note" id="approval_note" rows="3" class="block w-full text-sm border-gray-300 rounded-md"></textarea>
                            @error('note')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            ACC
                        </button>>
                    </form>
                </div>
            @endif
        @endcan

        @can('handoff', $item)
            @if ($item->status === \App\Enums\ItemStatus::Acc)
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-3">Serah Terima ke Produksi</h2>
                    <p class="text-sm text-gray-600 mb-3">
                        Desain telah di-ACC dan siap diserahkan ke antrean produksi.
                    </p>
                    <form method="POST" action="{{ route('design.handoff', $item) }}">
                        @csrf
                        @error('item')
                            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                            Serahkan ke Produksi
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
@endsection