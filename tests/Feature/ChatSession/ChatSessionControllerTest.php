<?php

use App\Ai\Agents\CareerCoach;
use App\Ai\Agents\ExperienceExtractor;
use App\Enums\ChatSessionMode;
use App\Enums\ChatSessionStatus;
use App\Models\ChatSession;
use App\Models\Experience;
use App\Models\JobPosting;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
});

// -- Index --

test('index renders with sessions list', function () {
    ChatSession::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/career-chat')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('career-chat/index')
            ->has('sessions', 3)
            ->has('jobPostings')
        );
});

test('index requires authentication', function () {
    $this->get('/career-chat')->assertRedirect('/login');
});

// -- Store --

test('store creates a general chat session', function () {
    $this->actingAs($this->user)
        ->post('/career-chat', [
            'title' => 'My Career Chat',
            'mode' => 'general',
        ])
        ->assertRedirect();

    $session = ChatSession::first();
    expect($session)
        ->title->toBe('My Career Chat')
        ->mode->toBe(ChatSessionMode::General)
        ->user_id->toBe($this->user->id)
        ->job_posting_id->toBeNull();
});

test('store creates a job-specific chat session', function () {
    $jobPosting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post('/career-chat', [
            'mode' => 'job_specific',
            'job_posting_id' => $jobPosting->id,
        ])
        ->assertRedirect();

    $session = ChatSession::first();
    expect($session)
        ->mode->toBe(ChatSessionMode::JobSpecific)
        ->job_posting_id->toBe($jobPosting->id);
});

test('store uses default title when none provided', function () {
    $this->actingAs($this->user)
        ->post('/career-chat', ['mode' => 'general'])
        ->assertRedirect();

    expect(ChatSession::first()->title)->toBe('New Chat');
});

test('store auto-generates title from job posting', function () {
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Senior Dev',
        'company' => 'Acme',
    ]);

    $this->actingAs($this->user)
        ->post('/career-chat', [
            'mode' => 'job_specific',
            'job_posting_id' => $jobPosting->id,
        ])
        ->assertRedirect();

    expect(ChatSession::first()->title)->toBe('Chat: Senior Dev at Acme');
});

// -- Show --

test('show loads session with messages', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/career-chat/{$session->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('career-chat/show')
            ->has('chatSession')
            ->has('messages')
            ->has('sessions')
        );
});

test('show returns 403 for other users sessions', function () {
    $other = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/career-chat/{$session->id}")
        ->assertForbidden();
});

// -- Chat --

test('chat returns AI response', function () {
    CareerCoach::fake(['Great! Tell me about your most recent role.']);

    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [
            'message' => 'Hello, I want to explore my experience.',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['message', 'conversation_id']);

    CareerCoach::assertPrompted('Hello, I want to explore my experience.');
});

test('chat sets conversation_id on first message', function () {
    CareerCoach::fake(['Hello!']);

    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'conversation_id' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [
            'message' => 'Hi',
        ])
        ->assertSuccessful();

    $session->refresh();
    expect($session->conversation_id)->not->toBeNull();
});

test('chat injects experience context', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'TestCorp',
        'title' => 'Lead Engineer',
    ]);

    CareerCoach::fake(['I see you worked at TestCorp.']);

    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [
            'message' => 'What do you know about me?',
        ])
        ->assertSuccessful();

    CareerCoach::assertPrompted(function ($prompt) {
        $instructions = $prompt->agent->instructions();

        return str_contains($instructions, 'TestCorp')
            && str_contains($instructions, 'Lead Engineer');
    });
});

test('chat injects job context for job-specific mode', function () {
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $this->user->id,
        'title' => 'Staff Engineer',
        'company' => 'BigTech',
    ]);

    CareerCoach::fake(['Let me help you prepare for the Staff Engineer role.']);

    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'mode' => ChatSessionMode::JobSpecific,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [
            'message' => 'Help me with this role.',
        ])
        ->assertSuccessful();

    CareerCoach::assertPrompted(function ($prompt) {
        $instructions = $prompt->agent->instructions();

        return str_contains($instructions, 'Staff Engineer')
            && str_contains($instructions, 'BigTech')
            && str_contains($instructions, 'TARGET JOB');
    });
});

test('chat returns 403 for other users session', function () {
    $other = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [
            'message' => 'test',
        ])
        ->assertForbidden();
});

test('chat validates message is required', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/chat", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

// -- Extract --

test('extract returns structured data from conversation', function () {
    $session = ChatSession::factory()->withConversation()->create([
        'user_id' => $this->user->id,
    ]);

    // Insert fake conversation messages
    DB::table('agent_conversation_messages')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $session->conversation_id,
        'user_id' => $this->user->id,
        'agent' => CareerCoach::class,
        'role' => 'user',
        'content' => 'I worked at Acme as a Senior Dev for 3 years.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $session->conversation_id,
        'user_id' => $this->user->id,
        'agent' => CareerCoach::class,
        'role' => 'assistant',
        'content' => 'That sounds like a great role! Tell me about your accomplishments.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ExperienceExtractor::fake();

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/extract")
        ->assertSuccessful()
        ->assertJsonStructure(['experiences', 'skills', 'accomplishments', 'education', 'projects']);
});

test('extract passes existing context to extractor', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'ExistingCorp',
        'title' => 'Senior Dev',
    ]);

    $session = ChatSession::factory()->withConversation()->create([
        'user_id' => $this->user->id,
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $session->conversation_id,
        'user_id' => $this->user->id,
        'agent' => \App\Ai\Agents\CareerCoach::class,
        'role' => 'user',
        'content' => 'I worked at NewCo.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ExperienceExtractor::fake();

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/extract")
        ->assertSuccessful();

    ExperienceExtractor::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'ExistingCorp')
            && str_contains($prompt->prompt, 'Senior Dev');
    });
});

test('index includes has_conversation flag', function () {
    ChatSession::factory()->create(['user_id' => $this->user->id, 'conversation_id' => null]);
    ChatSession::factory()->withConversation()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/career-chat')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('career-chat/index')
            ->has('sessions', 2)
            ->where('sessions.0.has_conversation', true)
            ->where('sessions.1.has_conversation', false)
        );
});

test('extract returns 422 when no conversation exists', function () {
    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'conversation_id' => null,
    ]);

    $this->actingAs($this->user)
        ->postJson("/career-chat/{$session->id}/extract")
        ->assertStatus(422);
});

// -- Commit --

test('commit imports entries to experience library', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/career-chat/{$session->id}/commit", [
            'experiences' => [
                ['company' => 'Acme Corp', 'title' => 'Engineer', 'started_at' => '2023-01-01', 'is_current' => true],
            ],
            'skills' => [
                ['name' => 'PHP', 'category' => 'technical'],
            ],
            'accomplishments' => [
                ['title' => 'Built API', 'description' => 'Designed REST API', 'experience_index' => 0],
            ],
            'education' => [
                ['type' => 'degree', 'institution' => 'MIT', 'title' => 'CS Degree'],
            ],
            'projects' => [
                ['name' => 'Portal', 'description' => 'Customer portal', 'experience_index' => 0],
            ],
        ])
        ->assertRedirect();

    expect(Experience::count())->toBe(1);
    expect(Skill::count())->toBe(1);
    expect($this->user->accomplishments()->count())->toBe(1);
    expect($this->user->educationEntries()->count())->toBe(1);
    expect($this->user->projects()->count())->toBe(1);
});

test('commit returns 403 for other users session', function () {
    $other = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/career-chat/{$session->id}/commit", [])
        ->assertForbidden();
});

// -- Update --

test('update renames session', function () {
    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Old Title',
    ]);

    $this->actingAs($this->user)
        ->patch("/career-chat/{$session->id}", ['title' => 'New Title'])
        ->assertRedirect();

    expect($session->fresh()->title)->toBe('New Title');
});

test('update archives session', function () {
    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'status' => ChatSessionStatus::Active,
    ]);

    $this->actingAs($this->user)
        ->patch("/career-chat/{$session->id}", ['status' => 'archived'])
        ->assertRedirect();

    expect($session->fresh()->status)->toBe(ChatSessionStatus::Archived);
});

test('update returns 403 for other users session', function () {
    $other = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->patch("/career-chat/{$session->id}", ['title' => 'Hacked'])
        ->assertForbidden();
});
