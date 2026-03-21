<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_custom_category(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Freelance Income',
            'type' => 'income',
            'color' => '#3b82f6',
            'icon' => 'briefcase',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Freelance Income');
    }

    public function test_can_list_categories(): void
    {
        $user = $this->createUser();
        $bt = $this->getBT($user);
        Category::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $bt->id,
            'name' => 'My Category',
            'type' => 'expense',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_category_type_validation(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Invalid Category',
            'type' => 'invalid_type',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_update_own_category(): void
    {
        $user = $this->createUser();
        $bt = $this->getBT($user);
        $category = Category::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $bt->id,
            'name' => 'Old Name',
            'type' => 'expense',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'New Name',
            'type' => 'expense',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_delete_own_category(): void
    {
        $user = $this->createUser();
        $bt = $this->getBT($user);
        $category = Category::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $bt->id,
            'name' => 'To Delete',
            'type' => 'expense',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_cannot_update_other_users_category(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $otherBt = $this->getBT($other);
        $category = Category::create([
            'user_id' => $other->id,
            'budget_tracking_id' => $otherBt->id,
            'name' => 'Other Category',
            'type' => 'expense',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Hijacked',
        ]);
        $response->assertStatus(403);
    }

    public function test_category_creation_requires_name(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'type' => 'expense',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_category_creation_requires_type(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'No Type',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_valid_category_types_are_accepted(): void
    {
        $user = $this->createUser();
        $types = ['income', 'expense', 'investment', 'insurance', 'purchase', 'debt'];

        foreach ($types as $type) {
            $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
                'name' => "Category for {$type}",
                'type' => $type,
            ]);
            $response->assertStatus(201);
        }
    }
}
