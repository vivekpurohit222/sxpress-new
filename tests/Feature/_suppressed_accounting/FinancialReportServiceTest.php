<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FinancialReportService;
use Carbon\Carbon;

class FinancialReportServiceTest extends TestCase
{
    // ─── Indian Number Format Tests ──────────────────────────────────────────

    public function test_indian_number_format_100000(): void
    {
        $this->assertEquals('1,00,000.00', indianNumberFormat(100000));
    }

    public function test_indian_number_format_1234567_89(): void
    {
        $this->assertEquals('12,34,567.89', indianNumberFormat(1234567.89));
    }

    public function test_indian_number_format_999(): void
    {
        $this->assertEquals('999.00', indianNumberFormat(999));
    }

    public function test_indian_number_format_negative_50000(): void
    {
        $this->assertEquals('-50,000.00', indianNumberFormat(-50000));
    }

    public function test_indian_number_format_zero(): void
    {
        $this->assertEquals('0.00', indianNumberFormat(0));
    }

    // ─── Service Method Tests ────────────────────────────────────────────────

    public function test_get_day_book_returns_vouchers_for_today(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $date = Carbon::today()->format('Y-m-d');
        $result = $service->getDayBook($date);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('vouchers', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('debit', $result['totals']);
        $this->assertArrayHasKey('credit', $result['totals']);
    }

    public function test_get_trial_balance_returns_grouped_accounts(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $fromDate = '2024-04-01';
        $toDate = '2025-03-31';
        $result = $service->getTrialBalance($fromDate, $toDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('debit', $result['totals']);
        $this->assertArrayHasKey('credit', $result['totals']);
    }

    public function test_get_profit_and_loss_returns_income_expenses_net_profit_keys(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $fromDate = '2024-04-01';
        $toDate = '2025-03-31';
        $result = $service->getProfitAndLoss($fromDate, $toDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('income', $result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('net_profit', $result);
        $this->assertArrayHasKey('total_income', $result);
        $this->assertArrayHasKey('total_expenses', $result);
    }

    public function test_get_balance_sheet_returns_assets_liabilities_equity_net_profit_keys(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $asOfDate = Carbon::today()->format('Y-m-d');
        $result = $service->getBalanceSheet($asOfDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('net_profit', $result);
        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('total_liabilities_equity', $result);
    }

    public function test_get_vehicle_profitability_returns_vehicles_totals_keys(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $fromDate = '2024-04-01';
        $toDate = '2025-03-31';
        $result = $service->getVehicleProfitability($fromDate, $toDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('vehicles', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('revenue', $result['totals']);
        $this->assertArrayHasKey('expenses', $result['totals']);
        $this->assertArrayHasKey('net_profit', $result['totals']);
    }

    public function test_get_driver_expenses_returns_drivers_total_expenses_keys(): void
    {
        /** @var FinancialReportService $service */
        $service = app(FinancialReportService::class);

        $fromDate = '2024-04-01';
        $toDate = '2025-03-31';
        $result = $service->getDriverExpenses($fromDate, $toDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('drivers', $result);
        $this->assertArrayHasKey('total_expenses', $result);
    }
}
