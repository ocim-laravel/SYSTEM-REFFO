<?php

namespace App\Services\System;

use App\Models\User;

/**
 * Menyusun menu sidebar sesuai permission user (04-architecture §4.6).
 *
 * Blade tidak boleh memeriksa nama role secara langsung; seluruh
 * penyaringan dilakukan lewat permission/Gate di service ini.
 */
class MenuService
{
    /**
     * @return array<int, array{title: string, items: array<int, array{key: string, label: string, route: string, permission: string}>}>
     */
    public function for(User $user): array
    {
        $sections = [
            [
                'title' => 'Utama',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'menu.dashboard'],
                ],
            ],
            [
                'title' => 'Operasional',
                'items' => [
                    ['key' => 'customer', 'label' => 'Pelanggan', 'route' => 'customers.index', 'permission' => 'menu.customer'],
                    ['key' => 'order', 'label' => 'Order', 'route' => 'orders.index', 'permission' => 'menu.order'],
                    ['key' => 'design', 'label' => 'Desain', 'route' => 'designs.index', 'permission' => 'menu.design'],
                    ['key' => 'production', 'label' => 'Produksi', 'route' => 'production.index', 'permission' => 'menu.production'],
                    ['key' => 'finishing', 'label' => 'Finishing & QC', 'route' => 'finishing.index', 'permission' => 'menu.finishing'],
                    ['key' => 'delivery', 'label' => 'Pengiriman', 'route' => 'deliveries.index', 'permission' => 'menu.delivery'],
                    ['key' => 'finance', 'label' => 'Keuangan', 'route' => 'finance.index', 'permission' => 'menu.finance'],
                    ['key' => 'hrd', 'label' => 'HRD', 'route' => 'hrd.index', 'permission' => 'menu.hrd'],
                    ['key' => 'material', 'label' => 'Material', 'route' => 'materials.index', 'permission' => 'menu.material'],
                    ['key' => 'reports', 'label' => 'Laporan', 'route' => 'reports.index', 'permission' => 'menu.reports'],
                ],
            ],
            [
                'title' => 'Sistem',
                'items' => [
                    ['key' => 'divisions', 'label' => 'Divisi', 'route' => 'system.divisions.index', 'permission' => 'divisions.view'],
                    ['key' => 'users', 'label' => 'Pengguna', 'route' => 'system.users.index', 'permission' => 'users.view'],
                ],
            ],
        ];

        return array_values(array_filter(array_map(function (array $section) use ($user): array {
            $section['items'] = array_values(array_filter(
                $section['items'],
                fn (array $item): bool => $user->can($item['permission']),
            ));

            return $section;
        }, $sections), fn (array $section): bool => $section['items'] !== []));
    }
}
