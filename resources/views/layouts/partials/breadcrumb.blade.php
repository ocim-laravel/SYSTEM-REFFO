<nav class="breadcrumb" aria-label="Breadcrumb" style="margin-bottom:10px;font-size:13px;color:#6b7280">
    @foreach ($breadcrumbs as $index => $crumb)
        @if ($index > 0)
            <span style="margin:0 6px;color:#d1d5db">/</span>
        @endif
        @if (! $crumb['current'] && $crumb['url'] !== null)
            <a href="{{ $crumb['url'] }}" style="color:#374151;text-decoration:none">{{ $crumb['label'] }}</a>
        @elseif ($crumb['current'])
            <span aria-current="page">{{ $crumb['label'] }}</span>
        @else
            <span>{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</nav>
