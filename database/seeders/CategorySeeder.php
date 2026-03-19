<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Income categories
            ['name' => 'Salary', 'type' => 'income', 'color' => '#4CAF50', 'icon' => 'briefcase'],
            ['name' => 'Business', 'type' => 'income', 'color' => '#2196F3', 'icon' => 'store'],
            ['name' => 'Freelance', 'type' => 'income', 'color' => '#9C27B0', 'icon' => 'laptop'],
            ['name' => 'Investment Returns', 'type' => 'income', 'color' => '#FF9800', 'icon' => 'trending-up'],
            ['name' => 'Other Income', 'type' => 'income', 'color' => '#607D8B', 'icon' => 'plus-circle'],
            // Expense categories
            ['name' => 'Food', 'type' => 'expense', 'color' => '#F44336', 'icon' => 'utensils'],
            ['name' => 'Transportation', 'type' => 'expense', 'color' => '#FF5722', 'icon' => 'car'],
            ['name' => 'Housing', 'type' => 'expense', 'color' => '#795548', 'icon' => 'home'],
            ['name' => 'Healthcare', 'type' => 'expense', 'color' => '#E91E63', 'icon' => 'heart'],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#673AB7', 'icon' => 'music'],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => '#3F51B5', 'icon' => 'shopping-bag'],
            ['name' => 'Bills', 'type' => 'expense', 'color' => '#009688', 'icon' => 'file-text'],
            ['name' => 'Education', 'type' => 'expense', 'color' => '#00BCD4', 'icon' => 'book'],
            ['name' => 'Other Expense', 'type' => 'expense', 'color' => '#9E9E9E', 'icon' => 'more-horizontal'],
            // Investment categories
            ['name' => 'Stocks', 'type' => 'investment', 'color' => '#4CAF50', 'icon' => 'bar-chart-2'],
            ['name' => 'Cryptocurrency', 'type' => 'investment', 'color' => '#FF9800', 'icon' => 'cpu'],
            ['name' => 'Real Estate', 'type' => 'investment', 'color' => '#2196F3', 'icon' => 'building'],
            ['name' => 'Business Investment', 'type' => 'investment', 'color' => '#9C27B0', 'icon' => 'briefcase'],
            ['name' => 'Mutual Fund', 'type' => 'investment', 'color' => '#607D8B', 'icon' => 'pie-chart'],
            // Insurance categories
            ['name' => 'Life Insurance', 'type' => 'insurance', 'color' => '#4CAF50', 'icon' => 'shield'],
            ['name' => 'Health Insurance', 'type' => 'insurance', 'color' => '#E91E63', 'icon' => 'heart'],
            ['name' => 'Car Insurance', 'type' => 'insurance', 'color' => '#FF5722', 'icon' => 'car'],
            // Purchase categories
            ['name' => 'Electronics', 'type' => 'purchase', 'color' => '#2196F3', 'icon' => 'smartphone'],
            ['name' => 'Appliances', 'type' => 'purchase', 'color' => '#607D8B', 'icon' => 'tv'],
            ['name' => 'Furniture', 'type' => 'purchase', 'color' => '#795548', 'icon' => 'layout'],
            // Debt categories
            ['name' => 'Personal Loan', 'type' => 'debt', 'color' => '#F44336', 'icon' => 'credit-card'],
            ['name' => 'Business Loan', 'type' => 'debt', 'color' => '#FF9800', 'icon' => 'briefcase'],
            ['name' => 'Mortgage', 'type' => 'debt', 'color' => '#795548', 'icon' => 'home'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name'], 'type' => $cat['type'], 'user_id' => null],
                array_merge($cat, ['user_id' => null, 'is_system' => true])
            );
        }
    }
}
