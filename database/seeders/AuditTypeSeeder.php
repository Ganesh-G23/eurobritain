<?php

namespace Database\Seeders;

use App\Models\AuditType;
use Illuminate\Database\Seeder;

class AuditTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Pre Audit',
            'Certification',
            'Transfer',
            'Scope Change',
            'Recertification',
        ];

        foreach ($types as $name) {
            AuditType::query()->firstOrCreate(['name' => $name]);
        }
    }
}
