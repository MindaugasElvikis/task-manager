<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * @covers \App\Http\Controllers\TaskController
 */
class TaskControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_unauthenticated(): void
    {
        $response = $this->get('/api/tasks', [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_index_as_owner(): void
    {
        $user = User::factory()->create();
        $userTasks = Task::factory()->count(15)->for($user, 'owner')->create();
        $otherTasks = Task::factory()->count(3)->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $response = $this->get('/api/tasks?page=2', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonFragment(
            [
                'id' => $userTasks[11]->id,
                'name' => $userTasks[11]->name,
                'description' => $userTasks[11]->description,
                'type' => $userTasks[11]->type,
                'status' => $userTasks[11]->status,
                'owner' => [
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                ],
            ],
        );
        $response->assertJsonMissing(
            [
                'id' => $otherTasks[0]->id,
            ]
        );
        $response->assertJsonPath(
            'meta.pagination',
            [
                'total' => 15,
                'count' => 5,
                'per_page' => 10,
                'current_page' => 2,
                'total_pages' => 2,
                'links' => [
                    'previous' => 'http://localhost/api/tasks?page=1',
                ],
            ]
        );
    }

    public function test_index_as_assignee(): void
    {
        $user = User::factory()->hasAttached(
            Task::factory()->count(6),
            [],
            'assigned_tasks'
        )->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $response = $this->get('/api/tasks', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJsonCount(6, 'data');
        /** @var Task $firstAssignedTask */
        $firstAssignedTask = $user->assigned_tasks->first();
        $response->assertJsonFragment(
            [
                'id' => $firstAssignedTask->id,
                'name' => $firstAssignedTask->name,
                'description' => $firstAssignedTask->description,
                'type' => $firstAssignedTask->type,
                'status' => $firstAssignedTask->status,
                'owner' => [
                    'data' => [
                        'id' => $firstAssignedTask->owner->id,
                        'name' => $firstAssignedTask->owner->name,
                    ],
                ],
                'attached_users' => [
                    'data' => [
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                        ],
                    ],
                ],
            ],
        );
        $response->assertJsonPath(
            'meta.pagination',
            [
                'total' => 6,
                'count' => 6,
                'per_page' => 10,
                'current_page' => 1,
                'total_pages' => 1,
                'links' => [],
            ]
        );
    }

    public function test_store(): void
    {
        $usersToAttach = User::factory()->count(3)->create();
        $user = User::factory()->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $response = $this->post(
            '/api/tasks',
            [
                'name' => 'test name',
                'description' => 'test description',
                'type' => Task::TYPE_EXPERT,
                'status' => Task::STATUS_TODO,
                'attached_users' => $usersToAttach->map(function (User $user) {
                    return $user->id;
                })->toArray(),
            ],
            [
                'Authorization' => sprintf('Bearer %s', $token),
            ]
        );

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => 1,
                    'name' => 'test name',
                    'description' => 'test description',
                    'type' => Task::TYPE_EXPERT,
                    'status' => Task::STATUS_TODO,
                    'owner' => [
                        'data' => [
                            'id' => $user->id,
                            'name' => $user->name,
                        ],
                    ],
                    'attached_users' => [
                        'data' => [
                            [
                                'id' => $usersToAttach[0]->id,
                                'name' => $usersToAttach[0]->name,
                            ],
                            [
                                'id' => $usersToAttach[1]->id,
                                'name' => $usersToAttach[1]->name,
                            ],
                            [
                                'id' => $usersToAttach[2]->id,
                                'name' => $usersToAttach[2]->name,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $task = Task::query()->find(1);
        self::assertEquals('test name', $task->name);
        self::assertEquals('test description', $task->description);
        self::assertEquals(Task::TYPE_EXPERT, $task->type);
        self::assertEquals(Task::STATUS_TODO, $task->status);
        self::assertEquals($user->id, $task->owner->id);
    }

    public function test_show_unauthenticated(): void
    {
        $task = Task::factory()->create();
        $response = $this->get(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_show_forbidden(): void
    {
        $user = User::factory()->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $task = Task::factory()->create();

        $response = $this->get(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Forbidden.');
    }

    public function test_show_as_owner(): void
    {
        $task = Task::factory()->create();
        $token = auth()->attempt(['email' => $task->owner->email, 'password' => 'password']);

        $response = $this->get(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'type' => $task->type,
                    'status' => $task->status,
                    'owner' => [
                        'data' => [
                            'id' => $task->owner->id,
                            'name' => $task->owner->name,
                        ],
                    ],
                ],
            ]
        );
    }

    public function test_show_as_assignee(): void
    {
        $task = Task::factory()->hasAttached(
            User::factory()->count(1),
            [],
            'attached_users'
        )->create();
        $attachedUser = $task->attached_users->first();
        $token = auth()->attempt(['email' => $attachedUser->email, 'password' => 'password']);

        $response = $this->get(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'type' => $task->type,
                    'status' => $task->status,
                    'owner' => [
                        'data' => [
                            'id' => $task->owner->id,
                            'name' => $task->owner->name,
                        ],
                    ],
                    'attached_users' => [
                        'data' => [
                            [
                                'id' => $attachedUser->id,
                                'name' => $attachedUser->name,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function test_update_unauthenticated(): void
    {
        $task = Task::factory()->create();
        $response = $this->put(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_update_forbidden(): void
    {
        $user = User::factory()->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);
        $task = Task::factory()->create();

        $response = $this->put(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Forbidden.');
    }

    public function test_update_as_owner(): void
    {
        $usersToAttach = User::factory()->count(2)->create();
        $task = Task::factory()->hasAttached(
            User::factory()->count(5),
            [],
            'attached_users'
        )->create();
        $token = auth()->attempt(['email' => $task->owner->email, 'password' => 'password']);

        $response = $this->put(
            sprintf('/api/tasks/%s', $task->id),
            [
                'name' => 'test name',
                'description' => 'test description',
                'type' => Task::TYPE_ADVANCED,
                'status' => Task::STATUS_HOLD,
                'attached_users' => $usersToAttach->map(function (User $user) {
                    return $user->id;
                })->toArray(),
            ],
            [
                'Authorization' => sprintf('Bearer %s', $token),
            ]
        );

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => $task->id,
                    'name' => 'test name',
                    'description' => 'test description',
                    'type' => Task::TYPE_ADVANCED,
                    'status' => Task::STATUS_HOLD,
                    'owner' => [
                        'data' => [
                            'id' => $task->owner->id,
                            'name' => $task->owner->name,
                        ],
                    ],
                    'attached_users' => [
                        'data' => [
                            [
                                'id' => $usersToAttach[0]->id,
                                'name' => $usersToAttach[0]->name,
                            ],
                            [
                                'id' => $usersToAttach[1]->id,
                                'name' => $usersToAttach[1]->name,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $task->refresh();
        self::assertEquals('test name', $task->name);
        self::assertEquals('test description', $task->description);
        self::assertEquals(Task::TYPE_ADVANCED, $task->type);
        self::assertEquals(Task::STATUS_HOLD, $task->status);
    }

    public function test_destroy_unauthenticated(): void
    {
        $task = Task::factory()->create();
        $response = $this->delete(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_destroy_forbidden(): void
    {
        $user = User::factory()->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $task = Task::factory()->create();

        $response = $this->delete(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Forbidden.');
    }

    public function test_destroy_as_owner(): void
    {
        $task = Task::factory()->create();
        $token = auth()->attempt(['email' => $task->owner->email, 'password' => 'password']);

        $response = $this->delete(sprintf('/api/tasks/%s', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJson(
            [
                'message' => 'Ok.',
            ]
        );

        self::assertNull(Task::query()->find($task->id));
    }

    public function test_close_unauthenticated(): void
    {
        $task = Task::factory()->create();
        $response = $this->post(sprintf('/api/tasks/%s/close', $task->id), [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_close_forbidden(): void
    {
        $user = User::factory()->create();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $task = Task::factory()->create();

        $response = $this->post(sprintf('/api/tasks/%s/close', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Forbidden.');
    }

    public function test_close_as_owner(): void
    {
        $task = Task::factory()->create();
        $token = auth()->attempt(['email' => $task->owner->email, 'password' => 'password']);

        $response = $this->post(sprintf('/api/tasks/%s/close', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'type' => $task->type,
                    'status' => Task::STATUS_CLOSED,
                    'owner' => [
                        'data' => [
                            'id' => $task->owner->id,
                            'name' => $task->owner->name,
                        ],
                    ],
                ],
            ]
        );

        $task->refresh();
        self::assertEquals(Task::STATUS_CLOSED, $task->status);
    }

    public function test_close_as_assignee(): void
    {
        $task = Task::factory()->hasAttached(
            User::factory()->count(1),
            [],
            'attached_users'
        )->create();
        $assignedUser = $task->attached_users->first();
        $token = auth()->attempt(['email' => $assignedUser->email, 'password' => 'password']);

        $response = $this->post(sprintf('/api/tasks/%s/close', $task->id), [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertOk();
        $response->assertJson(
            [
                'data' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'type' => $task->type,
                    'status' => Task::STATUS_CLOSED,
                    'owner' => [
                        'data' => [
                            'id' => $task->owner->id,
                            'name' => $task->owner->name,
                        ],
                    ],
                ],
            ]
        );

        $task->refresh();
        self::assertEquals(Task::STATUS_CLOSED, $task->status);
    }
}
