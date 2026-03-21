<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAll(BudgetTracking $budget): Collection
    {
        return Category::where(function ($q) use ($budget) {
            $q->where('budget_tracking_id', $budget->id)->orWhereNull('budget_tracking_id');
        })->get();
    }

    public function create(BudgetTracking $budget, User $user, array $data): Category
    {
        return Category::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function seedDefaultCategories(BudgetTracking $budget, User $user): void
    {
        $defaults = [
            // Income categories
            ['name' => 'Salary', 'type' => 'income', 'color' => '#4CAF50', 'icon' => 'briefcase', 'is_system' => true],
            ['name' => 'Business', 'type' => 'income', 'color' => '#2196F3', 'icon' => 'store', 'is_system' => true],
            ['name' => 'Freelance', 'type' => 'income', 'color' => '#9C27B0', 'icon' => 'laptop', 'is_system' => true],
            ['name' => 'Investment Returns', 'type' => 'income', 'color' => '#FF9800', 'icon' => 'trending-up', 'is_system' => true],
            ['name' => 'Other Income', 'type' => 'income', 'color' => '#607D8B', 'icon' => 'plus-circle', 'is_system' => true],
            // Expense categories
            ['name' => 'Food', 'type' => 'expense', 'color' => '#F44336', 'icon' => 'utensils', 'is_system' => true],
            ['name' => 'Transportation', 'type' => 'expense', 'color' => '#FF5722', 'icon' => 'car', 'is_system' => true],
            ['name' => 'Housing', 'type' => 'expense', 'color' => '#795548', 'icon' => 'home', 'is_system' => true],
            ['name' => 'Healthcare', 'type' => 'expense', 'color' => '#E91E63', 'icon' => 'heart', 'is_system' => true],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#673AB7', 'icon' => 'music', 'is_system' => true],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => '#3F51B5', 'icon' => 'shopping-bag', 'is_system' => true],
            ['name' => 'Bills', 'type' => 'expense', 'color' => '#009688', 'icon' => 'file-text', 'is_system' => true],
            ['name' => 'Education', 'type' => 'expense', 'color' => '#00BCD4', 'icon' => 'book', 'is_system' => true],
            ['name' => 'Other Expense', 'type' => 'expense', 'color' => '#9E9E9E', 'icon' => 'more-horizontal', 'is_system' => true],
            // Investment categories
            ['name' => 'Stocks', 'type' => 'investment', 'color' => '#4CAF50', 'icon' => 'bar-chart-2', 'is_system' => true],
            ['name' => 'Cryptocurrency', 'type' => 'investment', 'color' => '#FF9800', 'icon' => 'cpu', 'is_system' => true],
            ['name' => 'Real Estate', 'type' => 'investment', 'color' => '#2196F3', 'icon' => 'building', 'is_system' => true],
            ['name' => 'Business Investment', 'type' => 'investment', 'color' => '#9C27B0', 'icon' => 'briefcase', 'is_system' => true],
            ['name' => 'Mutual Fund', 'type' => 'investment', 'color' => '#607D8B', 'icon' => 'pie-chart', 'is_system' => true],
        ];

        // Only create if not already existing for this budget tracker
        $existing = Category::where('budget_tracking_id', $budget->id)->where('is_system', true)->count();
        if ($existing === 0) {
            foreach ($defaults as $cat) {
                Category::firstOrCreate(
                    ['name' => $cat['name'], 'type' => $cat['type'], 'budget_tracking_id' => $budget->id],
                    array_merge($cat, ['budget_tracking_id' => $budget->id, 'user_id' => $user->id])
                );
            }
        }
    }
}
