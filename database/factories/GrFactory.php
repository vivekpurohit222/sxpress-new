<?php

namespace Database\Factories;

use App\Models\Gr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `grs` (Goods Receipt) table — legacy schema.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_22_053810_cretae_grs_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 *   (renames `nor_adress` → `consignor_address`, `nee_adress` → `consignee_address`,
 *    `nor_gst_no` → `consignor_gst_no`, `nee_gst_no` → `consignee_gst_no`)
 *
 * The factory is defensive: it works against both the legacy schema
 * (typo columns) and the modernized schema (renamed columns). It
 * detects which set of columns is present and adapts.
 *
 * Note: per erd.md §3 the modernized column names are:
 *   consignor_address, consignee_address, consignor_gst_no, consignee_gst_no
 * The factory uses those names when the table has been migrated; it
 * falls back to the legacy names otherwise.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Gr::factory()->create();
 *     Gr::factory()->count(200)->create();
 *     Gr::factory()->paid()->create();
 *     Gr::factory()->toPay()->create();
 *     Gr::factory()->fromOffice('RJKT')->create();
 */
class GrFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gr::class;

    /**
     * State — GR is paid.
     *
     * @return self
     */
    public function paid(): self
    {
        return $this->state(fn () => ['paid' => 1, 'to_pay' => 0]);
    }

    /**
     * State — GR is to-pay.
     *
     * @return self
     */
    public function toPay(): self
    {
        return $this->state(fn () => ['paid' => 0, 'to_pay' => 1]);
    }

    /**
     * State — pin a specific booking office.
     *
     * @param  string  $code
     * @return self
     */
    public function fromOffice(string $code): self
    {
        return $this->state(fn () => ['from_dest' => $code]);
    }

    /**
     * Define the model's default state.
     *
     * Money values are generated as plausible INR amounts. `weight` is
     * in kg. `nugs` is package count. `total_amount` is the sum of
     * the line items.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frieghtAmount = $this->faker->randomFloat(2, 500, 25000);
        $surCharge     = $this->faker->randomFloat(2, 0, 500);
        $cR            = $this->faker->randomFloat(2, 0, 300);
        $other         = $this->faker->randomFloat(2, 0, 1500);  // GST
        $bcAmount      = $this->faker->randomFloat(2, 0, 200);
        $billAmount    = $this->faker->randomFloat(2, 1000, 200000);
        $totalAmount   = $frieghtAmount + $surCharge + $cR + $other + $bcAmount;
        $weight        = $this->faker->randomFloat(3, 50, 5000);

        $consignor = $this->faker->company();
        $consignee = $this->faker->company();

        $payload = [
            'gr_no'             => $this->faker->unique()->bothify('??-#####'),
            'from_dest'         => $this->faker->randomElement(['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)']),
            'to_dest'           => $this->faker->randomElement(['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)']),
            'copy_date'         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'consignor'         => $consignor,
            'consignee'         => $consignee,
            'nugs'              => $this->faker->numberBetween(1, 200),
            'meth'              => $this->faker->randomElement(['C_R', 'C_B', 'Bags']),
            'description'       => $this->faker->randomElement([
                'Cotton bales', 'Cement bags', 'Steel rods', 'Fertilizer sacks',
                'Textile rolls', 'Sugar bags', 'Rice bags', 'Wheat bags',
                'Machinery parts', 'Tiles and marble', 'Spare parts', 'Garments',
            ]),
            'pm'                => $this->faker->randomElement(['Paid', 'To Pay', 'TBB', 'FOC']),
            'eway_bill_number'  => $this->faker->optional(0.8)->numerify('############'),
            'bill_amount'       => $billAmount,
            'weight'            => $weight,
            'paid'              => 0,
            'to_pay'            => 0,
            'frieght_amount'    => $frieghtAmount,
            'sur_ch'            => $surCharge,
            'c_r'               => $cR,
            'other'             => $other,
            'bc_amount'         => $bcAmount,
            'total_amount'      => $totalAmount,
        ];

        // Consignor / consignee address & GST — modernized (canonical) names
        // are preferred; we fall back to the typo names on the legacy schema.
        $consignorAddress = $this->faker->streetAddress().', '.$this->faker->city().', '.$this->faker->state();
        $consigneeAddress = $this->faker->streetAddress().', '.$this->faker->city().', '.$this->faker->state();
        $consignorGst     = $this->faker->optional(0.7)->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}1Z[A-Z]{1}');
        $consigneeGst     = $this->faker->optional(0.7)->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}1Z[A-Z]{1}');

        if (\Schema::hasColumn('grs', 'consignor_address')) {
            $payload['consignor_address'] = $consignorAddress;
        } else {
            $payload['nor_adress'] = $consignorAddress;
        }
        if (\Schema::hasColumn('grs', 'consignee_address')) {
            $payload['consignee_address'] = $consigneeAddress;
        } else {
            $payload['nee_adress'] = $consigneeAddress;
        }
        if (\Schema::hasColumn('grs', 'consignor_gst_no')) {
            $payload['consignor_gst_no'] = $consignorGst;
        } else {
            $payload['nor_gst_no'] = $consignorGst;
        }
        if (\Schema::hasColumn('grs', 'consignee_gst_no')) {
            $payload['consignee_gst_no'] = $consigneeGst;
        } else {
            $payload['nee_gst_no'] = $consigneeGst;
        }

        // Status column (added by migration 000021).
        if (\Schema::hasColumn('grs', 'status')) {
            $payload['status'] = $this->faker->randomElement(['booked', 'in_transit', 'delivered', 'closed']);
        }

        return $payload;
    }
}
