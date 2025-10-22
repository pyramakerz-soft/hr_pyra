export interface CustomVacation {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  is_full_day: boolean;
  description?: string | null;
  departments: Array<{
    id: number;
    name: string;
  }>;
  sub_departments: Array<{
    id: number;
    name: string;
    department_id: number;
  }>;
}

export interface CustomVacationPayload {
  name: string;
  start_date: string;
  end_date: string;
  is_full_day: boolean;
  description?: string | null;
  department_ids: number[];
  sub_department_ids: number[];
}
