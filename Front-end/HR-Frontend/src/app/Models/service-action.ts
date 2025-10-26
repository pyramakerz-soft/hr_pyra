export interface ServiceActionPayload {
  date?: string;
  clock_out_time?: string;
  default_duration_minutes?: number;
  from_date?: string;
  to_date?: string;
}

export interface ServiceActionRecord {
  id: number;
  action_type: string;
  scope_type: string;
  scope_id?: number | null;
  status: string;
  payload?: ServiceActionPayload | null;
  result?: any;
  created_at: string;
  performer?: {
    id: number;
    name: string;
  } | null;
}

export interface ServiceActionOptionField {
  key: string;
  label: string;
  type: 'date' | 'time' | 'number';
  required: boolean;
}

export interface ServiceActionDefinition {
  key: string;
  label: string;
  description: string;
  payload_fields: ServiceActionOptionField[];
}

