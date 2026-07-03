<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Suggestion;
use App\Models\User;

class SuggestionSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        Suggestion::create([
            'name' => 'تحسين واجهة المستخدم',
            'description' => 'اقتراح لتبسيط واجهة المستخدم في شاشة المنتج',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
