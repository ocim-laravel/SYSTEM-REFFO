@php($menu = app(\App\Services\System\MenuService::class)->for(auth()->user()))
<aside class="sidebar">
    <div class="sidebar-brand">
        <strong>{{ config('app.name') }}</strong>
        <div class="muted">{{ auth()->user()->name }}</div>
        <span class="badge">{{ auth()->user()->roleEnum()?->label() ?? '-' }}</span>
        @if (auth()->user()->division)
            <div class="muted">{{ auth()->user()->division->name }}</div>
        @endif
    </div>

    <nav>
        @foreach ($menu as $section)
            <div class="menu-section">
                <div class="menu-title">{{ $section['title'] }}</div>
                @foreach ($section['items'] as $item)
                    @if (\Illuminate\Support\Facades\Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           data-menu="{{ $item['key'] }}"
                           class="menu-link @if (request()->routeIs($item['route'])) active @endif">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span data-menu="{{ $item['key'] }}" class="menu-link disabled">{{ $item['label'] }} <small>(segera)</small></span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
