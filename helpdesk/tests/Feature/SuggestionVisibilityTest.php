<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuggestionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_sees_only_own_tickets_in_suggestions()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);

        Ticket::create([ 'subject' => 'UserOnly - laptop', 'description' => 'won\'t boot', 'category' => 'hardware', 'severity' => 3, 'created_by' => $user->id ]);
        Ticket::create([ 'subject' => 'OtherOnly - laptop', 'description' => 'stuck', 'category' => 'hardware', 'severity' => 3, 'created_by' => $other->id ]);

        $resp = $this->actingAs($user)->getJson(route('tickets.suggestions', ['subject' => 'laptop']));
        $resp->assertStatus(200);
        $data = $resp->json();

        $subjects = array_map(fn($i) => $i['ticket']['subject'], $data);
        $this->assertContains('UserOnly - laptop', $subjects);
        $this->assertNotContains('OtherOnly - laptop', $subjects);
    }

    public function test_agent_sees_all_matching_tickets_in_suggestions()
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $u1 = User::factory()->create(['role' => 'employee']);

        Ticket::create([ 'subject' => 'UserTicket - wifi', 'description' => 'auth failed', 'category' => 'network', 'severity' => 3, 'created_by' => $u1->id ]);
        Ticket::create([ 'subject' => 'OtherTicket - wifi', 'description' => 'no dhcp', 'category' => 'network', 'severity' => 3, 'created_by' => $agent->id ]);

        $resp = $this->actingAs($agent)->getJson(route('tickets.suggestions', ['subject' => 'wifi']));
        $resp->assertStatus(200);
        $subjects = array_map(fn($i) => $i['ticket']['subject'], $resp->json());

        $this->assertContains('UserTicket - wifi', $subjects);
        $this->assertContains('OtherTicket - wifi', $subjects);
    }
}
