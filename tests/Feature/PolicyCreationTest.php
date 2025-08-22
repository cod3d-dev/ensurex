<?php

namespace Tests\Feature;

use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filament\Resources\PolicyResource\Pages\EditCompletePolicyCreation;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PolicyCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_creation_action_updates_existing_policy_when_selected(): void
    {
        // Create a test user
        $user = User::factory()->create();

        // Create a draft policy
        $policy = Policy::factory()->create([
            'status' => PolicyStatus::Draft,
            'policy_type' => PolicyType::Health,
        ]);

        // Mock the areRequiredPagesCompleted method to return true
        $policy->shouldReceive('areRequiredPagesCompleted')->andReturn(true);

        // Test the action with the current policy type selected
        Livewire::actingAs($user)
            ->test(EditCompletePolicyCreation::class, ['record' => $policy])
            ->set('quote_policy_types', [PolicyType::Health->value])
            ->call('Crear Polizas');

        // Assert the policy status was updated to Created
        $this->assertEquals(PolicyStatus::Created, $policy->fresh()->status);
    }

    public function test_policy_creation_action_creates_new_policies_for_selected_types(): void
    {
        // Create a test user
        $user = User::factory()->create();

        // Create a draft policy
        $policy = Policy::factory()->create([
            'status' => PolicyStatus::Draft,
            'policy_type' => PolicyType::Health,
        ]);

        // Mock the areRequiredPagesCompleted method to return true
        $policy->shouldReceive('areRequiredPagesCompleted')->andReturn(true);

        // Initial policy count
        $initialCount = Policy::count();

        // Test the action with multiple policy types selected
        Livewire::actingAs($user)
            ->test(EditCompletePolicyCreation::class, ['record' => $policy])
            ->set('quote_policy_types', [
                PolicyType::Health->value,
                PolicyType::Life->value,
                PolicyType::Auto->value,
            ])
            ->call('Crear Polizas');

        // Assert that new policies were created (2 new ones)
        $this->assertEquals($initialCount + 2, Policy::count());

        // Assert the original policy was updated
        $this->assertEquals(PolicyStatus::Created, $policy->fresh()->status);

        // Assert that we have policies of each selected type
        $this->assertEquals(1, Policy::where('policy_type', PolicyType::Health)->count());
        $this->assertEquals(1, Policy::where('policy_type', PolicyType::Life)->count());
        $this->assertEquals(1, Policy::where('policy_type', PolicyType::Auto)->count());
    }
}
