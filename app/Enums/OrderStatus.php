<?php

namespace App\Enums;

/**
 * Status order (MOD-02, PH-02).
 *
 * Nilai mengikuti 05-data-model.md bagian "Status (Enum)" persis.
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingValidation = 'pending_validation';
    case Validated = 'validated';
    case InDesign = 'in_design';
    case DesignApproved = 'design_approved';
    case InProduction = 'in_production';
    case Finished = 'finished';
    case Delivering = 'delivering';
    case Installing = 'installing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingValidation => 'Menunggu Validasi',
            self::Validated => 'Tervalidasi',
            self::InDesign => 'Dalam Desain',
            self::DesignApproved => 'Desain Disetujui',
            self::InProduction => 'Dalam Produksi',
            self::Finished => 'Produksi Selesai',
            self::Delivering => 'Dalam Pengiriman',
            self::Installing => 'Dalam Pemasangan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
