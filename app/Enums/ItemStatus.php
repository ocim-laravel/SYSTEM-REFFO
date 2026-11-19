<?php

namespace App\Enums;

/**
 * Status item order — siklus hidup tiap item berdiri sendiri (MOD-02, BR-02).
 *
 * Nilai mengikuti 05-data-model.md bagian "Status (Enum)" persis.
 */
enum ItemStatus: string
{
    case Draft = 'draft';
    case QueuedDesign = 'queued_design';
    case Designing = 'designing';
    case Revision = 'revision';
    case AwaitingAcc = 'awaiting_acc';
    case Acc = 'acc';
    case QueuedProduction = 'queued_production';
    case Printing = 'printing';
    case Printed = 'printed';
    case Finishing = 'finishing';
    case Qc = 'qc';
    case Rework = 'rework';
    case Packing = 'packing';
    case Packed = 'packed';
    case Scheduled = 'scheduled';
    case Delivering = 'delivering';
    case Installing = 'installing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::QueuedDesign => 'Antrean Desain',
            self::Designing => 'Sedang Didesain',
            self::Revision => 'Revisi',
            self::AwaitingAcc => 'Menunggu ACC',
            self::Acc => 'ACC',
            self::QueuedProduction => 'Antrean Produksi',
            self::Printing => 'Sedang Cetak',
            self::Printed => 'Sudah Cetak',
            self::Finishing => 'Finishing',
            self::Qc => 'QC',
            self::Rework => 'Rework',
            self::Packing => 'Packing',
            self::Packed => 'Dikemas',
            self::Scheduled => 'Dijadwalkan',
            self::Delivering => 'Dalam Pengiriman',
            self::Installing => 'Dalam Pemasangan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
