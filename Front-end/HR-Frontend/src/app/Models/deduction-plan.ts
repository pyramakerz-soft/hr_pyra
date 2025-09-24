export interface DeductionPenalty {
  type: string;
  value?: number | null;
  unit?: string | null;
  meta?: { [key: string]: any } | null;
}

export interface DeductionRule {
  label: string;
  category: string;
  scope: string;
  order?: number;
  when: { [key: string]: any };
  penalty: DeductionPenalty;
  color?: string | null;
  stop_processing?: boolean;
  notes?: string | null;
  meta?: { [key: string]: any } | null;
}

export interface DeductionPlan {
  overwrite?: boolean;
  grace_minutes?: number | null;
  rules: DeductionRule[];
}

export interface ResolvedDeductionPlan extends DeductionPlan {
  sources?: Array<{ type: string; id: number | string; overwrite: boolean }>;
}
