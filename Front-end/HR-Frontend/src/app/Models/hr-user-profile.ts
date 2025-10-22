export interface HrUserSummary {
  id: number;
  name: string;
  code?: string | null;
  department?: string | null;
  sub_department?: string | null;
}

export interface HrUserDetailInfo {
  salary?: number | null;
  hourly_rate?: number | null;
  overtime_hourly_rate?: number | null;
  working_hours_day?: number | null;
  overtime_hours?: number | null;
  start_time?: string | null;
  end_time?: string | null;
  emp_type?: string | null;
  hiring_date?: string | null;
}

export interface HrVacationBalance {
  id?: number;
  vacation_type_id: number;
  vacation_type_name?: string;
  year: number;
  allocated_days: number;
  used_days: number;
  remaining_days: number;
}

export interface HrVacationTypeOption {
  id: number;
  name: string;
  default_days: number;
}

export interface HrUserProfile {
  user: HrUserSummary;
  detail: HrUserDetailInfo;
  vacation_balances: HrVacationBalance[];
  vacation_types: HrVacationTypeOption[];
}

export interface HrUserProfileUpdatePayload {
  detail?: Partial<HrUserDetailInfo> | null;
  vacation_balances?: Array<{
    id?: number;
    vacation_type_id: number;
    year?: number;
    allocated_days: number;
    used_days?: number;
  }>;
  vacation_balance_ids_to_delete?: number[];
}

