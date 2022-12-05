<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionCategorySeeder extends Seeder
{
    protected $categories = [
        'رزومه',
        'کاورلتر',
        'مصاحبه',
        'کاریابی',
        'قرارداد',
        'انتخاب کشور',
        'انتخاب تخصص',
    ];

    public function run()
    {
        foreach ($this->categories as $id => $category) {
            \App\Models\QuestionCategory::factory()->create([
                'title'  => $category,
                'order'  => ($id + 1) * 10,
                'status' => 'published',
            ]);
        }
    }
}
