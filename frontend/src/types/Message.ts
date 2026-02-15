// src/types/Message.ts

export interface User {
  id: number;
  username: string;
  employee: string;
}

export interface Group {
  id: number;
  name: string;
  is_member: boolean;
}

export interface Message {
  id?: number;
  title: string;
  body: string;
  sender_id: string | number;
  sender_type: string;
  recipient_id: number | string;
  recipient_type: string;
  recipient_folder_id: number | null;
  sender_folder_id: number | null;
  group_id: number | null;
  is_anonymous: boolean;
  is_read: boolean;
  created_at?: Date | null;
  read_at: Date | null;
  sender_note: string;
  recipient_note: string;
  sender_name: string;
  recipient_name: string;
  deleted_by_sender: boolean; // Hinzufügen von deleted_by_sender
  deleted_by_recipient: boolean; // Hinzufügen von deleted_by_recipient
  pinned: boolean;
  recipient_folder_name?: string; // Optional, falls der Ordnername benötigt wird
  sender_folder_name?: string; // Optional, falls der Ordnername benötigt wird
}


export interface Folder {
  id: number;
  name: string;
  user_id: number | null;
  group_id: number | null;
}
