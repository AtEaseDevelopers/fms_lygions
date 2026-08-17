<?php

namespace Tests\Feature;

use App\Http\Controllers\ConsignmentController;
use App\Models\Consignment;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * The Truck Planning listing exports the SAME rows the user is filtering — using
 * the identical date-range / status / truck-number / search filters — but every
 * matching row, not just the current page. The download is a CSV (opens in Excel).
 */
class ConsignmentExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-17 09:00:00'));

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('load_date')->nullable();
            $table->string('consignment_no')->nullable();
            $table->string('consignor')->nullable();
            $table->string('pick_point')->nullable();
            $table->string('pick_address')->nullable();
            $table->string('consignee')->nullable();
            $table->string('drop_point')->nullable();
            $table->string('drop_address')->nullable();
            $table->string('pick_truck_type')->nullable();
            $table->string('drop_truck_type')->nullable();
            $table->string('pick_truck_size')->nullable();
            $table->string('drop_truck_size')->nullable();
            $table->string('pick_time')->nullable();
            $table->text('quantity')->nullable();
            $table->text('unit')->nullable();
            $table->boolean('pre_pick')->default(false);
            $table->string('remarks')->nullable();
            $table->string('billing_remark')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('consignments');
        parent::tearDown();
    }

    private function streamedContent(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    private function export(array $params = []): StreamedResponse
    {
        return (new ConsignmentController)->export(
            Request::create('/consignment-order/export', 'GET', $params)
        );
    }

    public function test_it_downloads_a_csv_with_a_header_row(): void
    {
        $response = $this->export();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('truck-planning-', $response->headers->get('Content-Disposition'));

        $csv = $this->streamedContent($response);
        $this->assertStringContainsString('Pick Up Date', $csv);
        $this->assertStringContainsString('Truck Number', $csv);
        $this->assertStringContainsString('Status', $csv);
    }

    public function test_it_defaults_to_current_month_onward_and_flattens_json_cells(): void
    {
        // No filter -> listing default is load_date >= start of current month.
        Consignment::create([
            'load_date' => '2026-08-20',
            'consignment_no' => 'CSN-FUTURE',
            'quantity' => json_encode(['10', '5']),
            'unit' => json_encode(['PLT', 'CTN']),
        ]);
        // First day of this month is still "current" -> visible.
        Consignment::create(['load_date' => '2026-08-01', 'consignment_no' => 'CSN-MONTHSTART']);
        // Earlier than today but same month -> still visible.
        Consignment::create(['load_date' => '2026-08-07', 'consignment_no' => 'CSN-EARLIER']);
        // Previous month -> archived, hidden here.
        Consignment::create(['load_date' => '2026-07-31', 'consignment_no' => 'CSN-LASTMONTH']);

        $csv = $this->streamedContent($this->export());

        $this->assertStringContainsString('CSN-FUTURE', $csv);
        $this->assertStringContainsString('CSN-MONTHSTART', $csv);
        $this->assertStringContainsString('CSN-EARLIER', $csv);
        $this->assertStringNotContainsString('CSN-LASTMONTH', $csv);
        // JSON arrays are joined for a single spreadsheet cell.
        $this->assertStringContainsString('10, 5', $csv);
        $this->assertStringContainsString('PLT, CTN', $csv);
    }

    public function test_it_honours_the_date_range_filter(): void
    {
        Consignment::create(['load_date' => '2026-09-05', 'consignment_no' => 'CSN-IN']);
        Consignment::create(['load_date' => '2026-09-20', 'consignment_no' => 'CSN-OUT']);

        $csv = $this->streamedContent($this->export([
            'filter_daterange' => '2026-09-01 to 2026-09-10',
        ]));

        $this->assertStringContainsString('CSN-IN', $csv);
        $this->assertStringNotContainsString('CSN-OUT', $csv);
    }

    public function test_it_follows_the_listing_sort_order(): void
    {
        Consignment::create(['load_date' => '2026-08-25', 'consignment_no' => 'CSN-C', 'consignor' => 'Charlie']);
        Consignment::create(['load_date' => '2026-08-25', 'consignment_no' => 'CSN-A', 'consignor' => 'Alpha']);
        Consignment::create(['load_date' => '2026-08-25', 'consignment_no' => 'CSN-B', 'consignor' => 'Bravo']);

        $csv = $this->streamedContent($this->export([
            'sort_by' => 'consignor',
            'sort_order' => 'asc',
        ]));

        // Rows must appear Alpha, Bravo, Charlie — the same order the listing shows.
        $posA = strpos($csv, 'Alpha');
        $posB = strpos($csv, 'Bravo');
        $posC = strpos($csv, 'Charlie');
        $this->assertTrue($posA < $posB && $posB < $posC, 'CSV rows are not in the requested sort order.');

        // Reversing the direction reverses the row order.
        $descCsv = $this->streamedContent($this->export([
            'sort_by' => 'consignor',
            'sort_order' => 'desc',
        ]));
        $this->assertTrue(
            strpos($descCsv, 'Charlie') < strpos($descCsv, 'Bravo'),
            'Descending sort was not applied.'
        );
    }

    public function test_it_honours_the_status_and_truck_number_filters(): void
    {
        Consignment::create(['load_date' => '2026-08-20', 'consignment_no' => 'CSN-MATCH', 'status' => 'Planning', 'truck_number' => 'T1']);
        Consignment::create(['load_date' => '2026-08-20', 'consignment_no' => 'CSN-STATUS', 'status' => 'Pending', 'truck_number' => 'T1']);
        Consignment::create(['load_date' => '2026-08-20', 'consignment_no' => 'CSN-TRUCK', 'status' => 'Planning', 'truck_number' => 'T2']);

        $csv = $this->streamedContent($this->export([
            'status' => 'planning',
            'truck_number' => 'T1',
        ]));

        $this->assertStringContainsString('CSN-MATCH', $csv);
        $this->assertStringNotContainsString('CSN-STATUS', $csv);
        $this->assertStringNotContainsString('CSN-TRUCK', $csv);
    }
}
