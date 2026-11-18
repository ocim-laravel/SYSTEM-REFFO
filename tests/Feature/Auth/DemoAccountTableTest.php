<?php

namespace Tests\Feature\Auth;

use App\Services\System\DemoAccountService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoAccountTableTest extends TestCase
{
    #[Test]
    public function it_shows_the_demo_account_table_on_the_login_page_in_development(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Akun Demo');
        $response->assertSee(DemoAccountService::PASSWORD);
        $response->assertSee('Isi Form');

        foreach (DemoAccountService::accounts() as $account) {
            $response->assertSee($account['email']);
            $response->assertSee('data-copy="'.$account['email'].'"', false);
            $response->assertSee('data-fill-email="'.$account['email'].'"', false);
        }
    }

    #[Test]
    public function it_hides_the_demo_account_table_on_the_login_page_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee('Akun Demo');
        $response->assertDontSee('owner@reffo.test');
        $response->assertDontSee(DemoAccountService::PASSWORD);
    }
}
