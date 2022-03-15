<?php
use Illuminate\Database\Seeder;
class PharmacyBranchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create 10 users using the user factory
        factory(App\Models\PharmacyBranch::class, 10)->create();
    }
}
