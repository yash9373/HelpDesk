<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_see_ticket_queue()
    {
        $agent = User::factory()->create(['role' => 'agent']);
        Ticket::create([ 'subject' => 'Queue - wifi', 'description' => 'auth failed', 'category' => 'network', 'severity' => 3, 'created_by' => $agent->id ]);

        $resp = $this->actingAs($agent)->get(route('tickets.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Queue - wifi');
    }

    public function test_agent_can_claim_ticket_from_queue()
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $u = User::factory()->create(['role' => 'employee']);

        $t = Ticket::create([ 'subject' => 'Claimable - laptop', 'description' => 'stuck', 'category' => 'hardware', 'created_by' => $u->id ]);

        $resp = $this->actingAs($agent)->post(route('tickets.claim', $t->id));
        $resp->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $t->id, 'assigned_to' => $agent->id, 'status' => 'in_progress']);
    }

    public function test_agent_can_assign_ticket_to_other_agent()
    {
        $agent1 = User::factory()->create(['role' => 'agent']);
        $agent2 = User::factory()->create(['role' => 'agent']);
        $u = User::factory()->create(['role' => 'employee']);

        $t = Ticket::create([ 'subject' => 'Assign - laptop', 'description' => 'stuck', 'category' => 'hardware', 'created_by' => $u->id ]);

        $resp = $this->actingAs($agent1)->post(route('tickets.assign', $t->id), ['assignee_id' => $agent2->id]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $t->id, 'assigned_to' => $agent2->id, 'status' => 'in_progress']);
    }

    public function test_employee_cannot_assign_ticket()
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $agent = User::factory()->create(['role' => 'agent']);

        $t = Ticket::create([ 'subject' => 'Cannot-assign', 'description' => 'stuck', 'category' => 'hardware', 'created_by' => $employee->id ]);

        $resp = $this->actingAs($employee)->post(route('tickets.assign', $t->id), ['assignee_id' => $agent->id]);
        $resp->assertStatus(403);
    }
}
