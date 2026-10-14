<?php

namespace Tests\Unit\Enums;

use App\Enums\TaskStatus;
use Tests\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_it_exposes_every_status_value(): void
    {
        $this->assertSame(['todo', 'in_progress', 'blocked', 'done'], TaskStatus::values());
    }

    public function test_only_done_is_closed(): void
    {
        $this->assertTrue(TaskStatus::Todo->isOpen());
        $this->assertTrue(TaskStatus::InProgress->isOpen());
        $this->assertTrue(TaskStatus::Blocked->isOpen());
        $this->assertFalse(TaskStatus::Done->isOpen());

        $this->assertSame(['todo', 'in_progress', 'blocked'], TaskStatus::openValues());
    }

    public function test_options_are_shaped_for_select_inputs(): void
    {
        $this->assertSame(
            ['value' => 'in_progress', 'label' => 'In progress'],
            TaskStatus::options()[1],
        );
    }
}
