export interface SystemNotificationRecord {
  id: number;
  type: string;
  title: string;
  message?: string;
  scope_type: string;
  scope_id?: number | null;
  filters?: Record<string, any> | null;
  recipients_count?: number;
  created_by?: {
    id: number;
    name: string;
  };
  created_at: string;
  formatted_created_at?: string;
}

