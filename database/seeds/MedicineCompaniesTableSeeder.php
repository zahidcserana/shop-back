<?php

use Illuminate\Database\Seeder;

class MedicineCompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\MedicineCompany::class, 1000)->create();

    }
}
