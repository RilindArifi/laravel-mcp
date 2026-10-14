export type ProjectStatusValue =
    | 'active'
    | 'on_hold'
    | 'completed'
    | 'archived';
export type TaskStatusValue = 'todo' | 'in_progress' | 'blocked' | 'done';
export type TaskPriorityValue = 'low' | 'medium' | 'high' | 'urgent';

export type SelectOption = {
    value: string;
    label: string;
};

export type LabelItem = {
    id: number;
    name: string;
    color: string;
};

export type Project = {
    id: number;
    name: string;
    description: string | null;
    status: ProjectStatusValue;
    status_label: string;
    due_date: string | null;
    tasks_count: number | null;
    open_tasks_count: number | null;
    completed_tasks_count: number | null;
    progress: number;
    updated_at: string | null;
};

export type ProjectOption = {
    id: number;
    name: string;
};

export type Task = {
    id: number;
    project_id: number;
    title: string;
    description: string | null;
    status: TaskStatusValue;
    status_label: string;
    priority: TaskPriorityValue;
    priority_label: string;
    due_date: string | null;
    is_overdue: boolean;
    completed_at: string | null;
    labels: LabelItem[];
    project?: ProjectOption;
};

export type WorkspaceStats = {
    active_projects: number;
    open_tasks: number;
    overdue_tasks: number;
    completed_this_week: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};
