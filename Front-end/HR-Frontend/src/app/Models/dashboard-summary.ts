export interface DashboardMetricSummary {
  pending_issues: number;
  open_clocks: number;
  pending_excuses: number;
  pending_overtime: number;
}

export interface PresenceTotals {
  employees: number;
  present: number;
  absent: number;
  on_leave: number;
  open_clocks: number;
}

export interface PresenceTrendPoint {
  date: string;
  present: number;
}

export interface PresenceSnapshot {
  date: string;
  totals: PresenceTotals;
  trend: PresenceTrendPoint[];
}

