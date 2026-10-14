<?php

namespace Tests\Unit\Models;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_a_task_to_done_stamps_the_completion_time(): void
    {
        $task = Task::factory()->create();

        $task->moveTo(TaskStatus::Done);

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_reopening_a_task_clears_the_completion_time(): void
    {
        $task = Task::factory()->done()->create();

        $task->moveTo(TaskStatus::Todo);

        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_a_task_due_today_is_not_overdue_yet(): void
    {
        $task = Task::factory()->create(['due_date' => today()]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_an_unfinished_task_past_its_due_date_is_overdue(): void
    {
        $task = Task::factory()->create(['due_date' => today()->subDay()]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_a_completed_task_is_never_overdue(): void
    {
        $task = Task::factory()->done()->create(['due_date' => today()->subWeek()]);

        $this->assertFalse($task->isOverdue());
    }
}
