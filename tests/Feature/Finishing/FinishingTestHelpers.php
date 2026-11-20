<?php

namespace Tests\Feature\Finishing;

use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\PrintJobStatus;
use App\Enums\PrintQueueClass;
use App\Enums\Priority;
use App\Enums\ReprintStatus;
use App\Models\FinishingJob;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\ReprintRequest;
use App\Models\User;
use App\Services\Production\FinishingService;

/**
 * Helper bersama untuk menyiapkan item printed (PH-04 selesai) pada test MOD-05.
 */
trait FinishingTestHelpers
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function printedItem(array $overrides = []): OrderItem
    {
        /** @var OrderItem $item */
        $item = OrderItem::factory()->create(array_merge([
            'title' => 'Item Finishing Uji',
            'category' => ItemCategory::Printing,
            'qty' => '10.00',
            'unit' => 'pcs',
            'unit_price' => '10000.00',
            'total' => '100000.00',
            'priority' => Priority::Normal,
            'status' => ItemStatus::Printed,
            'deadline_at' => '2026-12-10 10:00:00',
        ], $overrides));

        return $item;
    }

    protected function intakeItem(OrderItem $item, User $actor): FinishingJob
    {
        return app(FinishingService::class)->intake($item, $actor);
    }

    protected function completeJob(FinishingJob $job, User $actor): void
    {
        app(FinishingService::class)->recordProgress($job, [
            'started_at' => now()->subHour()->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'progress_note' => 'Finishing selesai dikerjakan.',
        ], $actor);
    }

    protected function approvedReprint(OrderItem $item, User $actor): ReprintRequest
    {
        $printJob = $item->printJob;

        if ($printJob === null) {
            /** @var PrintJob $printJob */
            $printJob = PrintJob::create([
                'order_item_id' => $item->id,
                'queue_class' => PrintQueueClass::Regular,
                'status' => PrintJobStatus::Completed,
                'qty_planned' => '10.00',
                'qty_actual' => '10.00',
            ]);
        }

        return ReprintRequest::create([
            'print_job_id' => $printJob->id,
            'qty' => '2.00',
            'reason' => 'Cetak ulang karena rework tahap cetak',
            'requested_by' => $actor->id,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'status' => ReprintStatus::Approved,
        ]);
    }
}
