<?php

namespace Tests\Feature\System;

use App\Services\System\NumberGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class NumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_cus_ord_and_itm_numbers(): void
    {
        $service = app(NumberGeneratorService::class);
        $year = now()->year;

        $this->assertSame(sprintf('CUS-%d-0001', $year), $service->nextCustomer());
        $this->assertSame(sprintf('ORD-%d-0001', $year), $service->nextOrder());
        $this->assertSame(sprintf('ITM-%d-0001', $year), $service->nextItem());
        $this->assertSame(sprintf('CUS-%d-0002', $year), $service->nextCustomer());
    }

    #[Test]
    public function it_resets_sequence_for_each_year(): void
    {
        $service = app(NumberGeneratorService::class);

        $this->assertSame('CUS-2025-0001', $service->nextCustomer(2025));
        $this->assertSame('CUS-2026-0001', $service->nextCustomer(2026));
        $this->assertSame('CUS-2026-0002', $service->nextCustomer(2026));
    }

    #[Test]
    public function it_rolls_back_a_number_when_the_surrounding_transaction_fails(): void
    {
        $service = app(NumberGeneratorService::class);

        try {
            DB::transaction(function () use ($service): void {
                $service->nextCustomer();
                throw new RuntimeException('gagal');
            });
        } catch (RuntimeException) {
            // Sengaja ditelan untuk menguji rollback.
        }

        $this->assertSame(sprintf('CUS-%d-0001', now()->year), $service->nextCustomer());
    }

    #[Test]
    public function it_keeps_each_prefix_in_its_own_sequence(): void
    {
        $service = app(NumberGeneratorService::class);

        $service->nextCustomer();
        $service->nextCustomer();
        $service->nextOrder();

        $this->assertSame(sprintf('ORD-%d-0002', now()->year), $service->nextOrder());
    }
}
