<?php

namespace App\Services\System;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Menyusun breadcrumb otomatis dari nama route (MOD-10, PH-00).
 *
 * Label memakai bahasa Indonesia dan tidak boleh ditempatkan di Blade
 * sebagai percabangan role/route.
 */
class BreadcrumbService
{
    /** @var array<string, string> */
    private const LABELS = [
        'dashboard' => 'Dashboard',
        'admin' => 'Admin',
        'operator' => 'Operator',
        'system' => 'Sistem',
        'divisions' => 'Divisi',
        'users' => 'Pengguna',
        'work-calendars' => 'Kalender Kerja',
        'overrides' => 'Override',
        'settings' => 'Pengaturan',
        'notifications' => 'Notifikasi',
        'password' => 'Keamanan',
        'change' => 'Ubah Password',
        'edit-password' => 'Reset Password',
        'reset-password' => 'Reset Password',
        'toggle-active' => 'Status Pengguna',
        'index' => '',
        'create' => 'Tambah',
        'edit' => 'Ubah',
        'store' => '',
        'update' => '',
        'destroy' => '',
    ];

    /**
     * @return list<array{label: string, url: string|null, current: bool}>
     */
    public function for(Request $request): array
    {
        $items = [['label' => 'Beranda', 'url' => route('dashboard'), 'current' => false]];
        $routeName = $request->route()?->getName();

        if ($routeName === null || $routeName === 'dashboard') {
            $items[0]['current'] = true;

            return $items;
        }

        $prefix = '';

        foreach (explode('.', $routeName) as $segment) {
            $label = self::LABELS[$segment] ?? Str::headline(str_replace(['-', '_'], ' ', $segment));

            if ($label === '') {
                continue;
            }

            $prefix = $prefix === '' ? $segment : $prefix.'.'.$segment;
            $items[] = [
                'label' => $label,
                'url' => $prefix !== $routeName && Route::has($prefix) ? route($prefix) : null,
                'current' => false,
            ];
        }

        $last = count($items) - 1;

        if ($last > 0) {
            $items[$last]['current'] = true;
            $items[$last]['url'] = null;
        }

        return $items;
    }
}
