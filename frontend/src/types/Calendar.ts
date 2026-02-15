export interface Event {
    id: number;
    title: string;
    content: string;
    start: string;
    end: string;
    color: string;
    contentFull: string;
    assigned_to: string[];
}

export interface AssignedUser {
    user_id: number;
    username: string;
}

export interface EventOverview {
    id: number;
    title: string;
    content: string;
    start: string;
    end: string;
    color: string;
    contentFull: string;
    assigned_to: AssignedUser[];
}