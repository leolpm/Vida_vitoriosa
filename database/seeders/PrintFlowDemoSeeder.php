<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PrintFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PrintFlowScenarioSeeder::class);
    }
}
