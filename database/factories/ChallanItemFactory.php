<?php

namespace Database\Factories;

use App\Models\ChallanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `challan_iteams` (Challan Items / Lines) table —
 * legacy schema.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_12_06_102452_create_challan_iteams_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 *
 * One challan_iteam = one line of a challan. The line is a
 * denormalized snapshot of a GR at dispatch time.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     ChallanItem::factory()->create();
 *     ChallanItem::factory()->count(50)->create();
 *     ChallanItem::factory()->forChallanAndGr($challanId, $grId)->create();
 */
class ChallanItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ChallanItem::class;

    /**
     * State — pin a specific challan + gr (modernized FKs).
     *
     * @param  int  $challanId
     * @param  int  $grId
     * @return self
     */
    public function forChallanAndGr(int $challanId, int $grId): self
    {
        return $this->state(fn () => [
            'challan_id' => $challanId,
            'gr_id'      => $grId,
        ]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $surCh = $this->faker->randomFloat(2, 0, 500);
        $cR    = $this->faker->randomFloat(2, 0, 300);
        $other = $this->faker->randomFloat(2, 0, 1500);
        $paid  = $this->faker->optional(0.3)->randomFloat(2, 1000, 20000);
        $toPay = $this->faker->optional(0.3)->randomFloat(2, 1000, 20000);

        $payload = [
            'gr_no'      => $this->faker->unique()->bothify('??-#####'),
            'challan_no' => $this->faker->bothify('CH-####'),
            'nugs'       => $this->faker->numberBetween(1, 200),
            'meth'       => $this->faker->randomElement(['C_R', 'C_B', 'Bags']),
            'description'=> $this->faker->randomElement(['Cotton bales', 'Cement bags', 'Steel rods', 'Fertilizer sacks', 'Textile rolls', 'Sugar bags']),
            'weight'     => $this->faker->randomFloat(3, 50, 5000),
            'paid'       => $paid,
            'to_pay'     => $toPay,
            'sur_ch'     => $surCh,
            'c_r'        => $cR,
            'other'      => $other,
        ];

        // Modernized FKs (migration 000010).
        if (\Schema::hasColumn('challan_iteams', 'gr_id')) {
            $payload['gr_id'] = null;
        }
        if (\Schema::hasColumn('challan_iteams', 'challan_id')) {
            $payload['challan_id'] = null;
        }

        return $payload;
    }
}
