import { DeductionPlan, DeductionRule } from '../Models/deduction-plan';

export type PlanConditionType = 'number' | 'boolean' | 'string' | 'weekday';

export interface PlanConditionOption {
  key: string;
  label: string;
  type: PlanConditionType;
  hint?: string;
}

export const PLAN_RULE_CATEGORIES: string[] = [
  'lateness',
  'deduction',
  'shortfall',
  'default_shortfall',
  'overtime',
  'vacation',
  'issue',
  'bonus',
  'other',
];

export const PLAN_PENALTY_TYPES = [
  { value: 'fixed_minutes', label: 'Fixed Minutes' },
  { value: 'fixed_hours', label: 'Fixed Hours' },
  { value: 'fraction_day', label: 'Fraction of Day' },
  { value: 'day', label: 'Full Day(s)' },
  { value: 'percentage_shortfall', label: 'Percentage of Shortfall' },
  { value: 'amount', label: 'Fixed Amount' },
];

export const PLAN_SCOPE_OPTIONS = ['occurrence', 'daily_total', 'per_day'];

export const WEEKDAY_OPTIONS = [
  { value: 'monday', label: 'Monday' },
  { value: 'tuesday', label: 'Tuesday' },
  { value: 'wednesday', label: 'Wednesday' },
  { value: 'thursday', label: 'Thursday' },
  { value: 'friday', label: 'Friday' },
  { value: 'saturday', label: 'Saturday' },
  { value: 'sunday', label: 'Sunday' },
];

export const PLAN_CONDITION_OPTIONS: PlanConditionOption[] = [
  { key: 'minutes_late_gte', label: 'Minutes late >=', type: 'number' },
  { key: 'minutes_late_lte', label: 'Minutes late <=', type: 'number' },
  { key: 'minutes_late_beyond_grace_gte', label: 'Minutes late beyond grace >=', type: 'number' },
  { key: 'minutes_late_beyond_grace_lte', label: 'Minutes late beyond grace <=', type: 'number' },
  { key: 'shortfall_minutes_gte', label: 'Shortfall minutes >=', type: 'number' },
  { key: 'shortfall_minutes_lte', label: 'Shortfall minutes <=', type: 'number' },
  { key: 'worked_minutes_gte', label: 'Worked minutes >=', type: 'number' },
  { key: 'worked_minutes_lte', label: 'Worked minutes <=', type: 'number' },
  { key: 'attendance_overtime_minutes_gte', label: 'Attendance overtime minutes >=', type: 'number' },
  { key: 'recorded_overtime_minutes_gte', label: 'Recorded or approved overtime minutes >=', type: 'number' },
  { key: 'occurrence_number', label: 'Exact occurrence number', type: 'number', hint: 'Applies only on the Nth time this rule category hits.' },
  { key: 'occurrence_every', label: 'Every Nth occurrence', type: 'number', hint: 'Applies on every Nth match of this rule category.' },
  { key: 'is_issue', label: 'Is issue day', type: 'boolean' },
  { key: 'is_vacation', label: 'Is vacation day', type: 'boolean' },
  { key: 'day_of_week_in', label: 'Day of week is', type: 'weekday' },
  { key: 'date_equals', label: 'Date equals', type: 'string' },
  { key: 'location_type_in', label: 'Location type is', type: 'string', hint: 'Applies when any recorded clock matches selected location types.' },
  { key: 'location_type_not_in', label: 'Location type is not', type: 'string', hint: 'Skips days that include the selected location types.' },
  { key: 'work_type_in', label: 'Employee work type is', type: 'string', hint: 'Matches assigned work types (e.g. site, home).' },
  { key: 'work_type_not_in', label: 'Employee work type is not', type: 'string', hint: 'Skips employees with any of these work types.' },
];

export function buildDefaultRule(): DeductionRule {
  return {
    label: '',
    category: 'lateness',
    scope: 'occurrence',
    when: {
      minutes_late_gte: 1,
      occurrence_number: 1,
    },
    penalty: {
      type: 'fixed_minutes',
      value: 0,
      unit: null,
    },
    color: null,
    stop_processing: false,
    notes: '',
  };
}

export function cloneRule(rule: DeductionRule): DeductionRule {
  return {
    label: rule.label ?? '',
    category: rule.category ?? 'other',
    scope: rule.scope ?? 'occurrence',
    order: rule.order,
    when: { ...(rule.when ?? {}) },
    penalty: {
      type: rule.penalty?.type ?? 'fixed_minutes',
      value: rule.penalty?.value ?? 0,
      unit: rule.penalty?.unit ?? null,
    },
    color: rule.color ?? null,
    stop_processing: !!rule.stop_processing,
    notes: rule.notes ?? null,
    meta: rule.meta ? { ...rule.meta } : undefined,
  };
}

export function clonePlan(plan?: DeductionPlan): DeductionPlan {
  const cloned: DeductionPlan = {
    overwrite: !!plan?.overwrite,
    overwrite_dep: !!plan?.overwrite_dep,
    overwrite_subdep: !!plan?.overwrite_subdep,
    grace_minutes: plan?.grace_minutes ?? 15,
    rules: Array.isArray(plan?.rules) && plan!.rules.length
      ? plan!.rules.map((rule) => cloneRule(rule))
      : [buildDefaultRule()],
    sources: plan?.sources ? [...plan.sources] : undefined,
  };

  return cloned;
}

export function getConditionOption(key: string): PlanConditionOption | undefined {
  return PLAN_CONDITION_OPTIONS.find((option) => option.key === key);
}

export function getConditionLabel(key: string): string {
  const option = getConditionOption(key);
  if (option) {
    return option.label;
  }

  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (match) => match.toUpperCase());
}

export function coerceConditionValue(key: string, rawValue: any): any {
  const option = getConditionOption(key);
  switch (option?.type) {
    case 'number': {
      if (rawValue === '' || rawValue === null || rawValue === undefined) {
        return null;
      }
      const numeric = Number(rawValue);
      return Number.isNaN(numeric) ? null : numeric;
    }
    case 'boolean':
      if (rawValue === '' || rawValue === null || rawValue === undefined) {
        return null;
      }
      if (typeof rawValue === 'boolean') {
        return rawValue;
      }
      if (rawValue === 1 || rawValue === '1' || rawValue === 'true' || rawValue === 'TRUE') {
        return true;
      }
      if (rawValue === 0 || rawValue === '0' || rawValue === 'false' || rawValue === 'FALSE') {
        return false;
      }
      return !!rawValue;
    case 'weekday':
      if (Array.isArray(rawValue)) {
        return rawValue.map((day) => String(day).toLowerCase());
      }
      if (typeof rawValue === 'string') {
        return rawValue
          .split(',')
          .map((day) => day.trim().toLowerCase())
          .filter((day) => day.length > 0);
      }
      return [];
    default:
      if (['location_type_in', 'location_type_not_in', 'work_type_in', 'work_type_not_in'].includes(key)) {
        if (Array.isArray(rawValue)) {
          return rawValue.map((value) => String(value).trim().toLowerCase()).filter((value) => value.length > 0);
        }
        if (typeof rawValue === 'string') {
          return rawValue
            .split(',')
            .map((value) => value.trim().toLowerCase())
            .filter((value) => value.length > 0);
        }
        return [];
      }
      return rawValue ?? '';
  }
}

export function defaultValueForCondition(key: string): any {
  const option = getConditionOption(key);
  if (!option) {
    return '';
  }

  switch (option.type) {
    case 'number':
      if (key.includes('occurrence')) {
        return 1;
      }
      if (key.includes('minutes_late')) {
        return 1;
      }
      return 0;
    case 'boolean':
      return false;
    case 'weekday':
      return ['monday'];
    default:
      if (key === 'location_type_in') {
        return ['site'];
      }
      if (key === 'location_type_not_in') {
        return ['home'];
      }
      if (key === 'work_type_in') {
        return ['site'];
      }
      if (key === 'work_type_not_in') {
        return ['home'];
      }
      return '';
  }
}

export class DeductionPlanEditor {
  plan: DeductionPlan;
  selectedConditions: Record<number, string | null> = {};
  customConditionDrafts: Record<number, { key: string; value: string }> = {};

  constructor(plan?: DeductionPlan) {
    this.plan = clonePlan(plan);
  }

  setPlan(plan?: DeductionPlan): void {
    this.plan = clonePlan(plan);
    this.resetDrafts();
  }

  get rules(): DeductionRule[] {
    return this.plan.rules;
  }

  addRule(): void {
    this.plan.rules = [...(this.plan.rules ?? []), buildDefaultRule()];
    this.resetDrafts();
  }

  removeRule(index: number): void {
    if (!this.plan.rules || this.plan.rules.length <= 1) {
      this.plan.rules = [buildDefaultRule()];
      this.resetDrafts();
      return;
    }

    this.plan.rules.splice(index, 1);
    this.plan.rules = [...this.plan.rules];
    this.resetDrafts();
  }

  updateGraceMinutes(value: any): void {
    if (value === '' || value === null || value === undefined) {
      this.plan.grace_minutes = null;
      return;
    }

    const numeric = Number(value);
    this.plan.grace_minutes = Number.isNaN(numeric)
      ? this.plan.grace_minutes ?? null
      : numeric;
  }

  setOverwrite(value: boolean): void {
    this.plan.overwrite = !!value;
  }

  setOverwriteDepartment(value: boolean): void {
    this.plan.overwrite_dep = !!value;
  }

  setOverwriteSubDepartment(value: boolean): void {
    this.plan.overwrite_subdep = !!value;
  }

  getConditionEntries(rule: DeductionRule): Array<{ key: string; value: any }> {
    return Object.entries(rule.when ?? {}).map(([key, value]) => ({ key, value }));
  }

  getAvailableConditionOptions(rule: DeductionRule): PlanConditionOption[] {
    const used = new Set(Object.keys(rule.when ?? {}));
    return PLAN_CONDITION_OPTIONS.filter((option) => !used.has(option.key));
  }

  setSelectedCondition(index: number, key: string | null): void {
    this.selectedConditions[index] = key;
  }

  addSelectedCondition(index: number): void {
    const key = this.selectedConditions[index];
    if (!key) {
      return;
    }

    this.addCondition(index, key);
    this.selectedConditions[index] = null;
  }

  addCondition(index: number, key: string, value?: any): void {
    const rule = this.plan.rules[index];
    if (!rule) {
      return;
    }

    rule.when = { ...(rule.when ?? {}) };
    const finalValue = value !== undefined ? value : defaultValueForCondition(key);
    rule.when[key] = finalValue;
  }

  removeCondition(index: number, key: string): void {
    const rule = this.plan.rules[index];
    if (!rule?.when) {
      return;
    }

    if (Object.prototype.hasOwnProperty.call(rule.when, key)) {
      delete rule.when[key];
      rule.when = { ...rule.when };
    }
  }

  updateConditionValue(index: number, key: string, rawValue: any): void {
    const rule = this.plan.rules[index];
    if (!rule) {
      return;
    }

    rule.when = { ...(rule.when ?? {}) };
    const coerced = coerceConditionValue(key, rawValue);

    if (coerced === null || (Array.isArray(coerced) && coerced.length === 0)) {
      delete rule.when[key];
      rule.when = { ...rule.when };
      return;
    }

    rule.when[key] = coerced;
  }

  getCustomDraft(index: number): { key: string; value: string } {
    if (!this.customConditionDrafts[index]) {
      this.customConditionDrafts[index] = { key: '', value: '' };
    }

    return this.customConditionDrafts[index];
  }

  addCustomCondition(index: number): void {
    const draft = this.getCustomDraft(index);
    const key = draft.key.trim();

    if (!key) {
      return;
    }

    const parsedValue = this.parseCustomValue(draft.value);
    this.addCondition(index, key, parsedValue);
    this.customConditionDrafts[index] = { key: '', value: '' };
  }

  private parseCustomValue(value: string): any {
    const trimmed = value.trim();
    if (trimmed === '') {
      return '';
    }

    if (trimmed === 'true' || trimmed === 'false') {
      return trimmed === 'true';
    }

    const numeric = Number(trimmed);
    if (!Number.isNaN(numeric)) {
      return numeric;
    }

    if (trimmed.includes(',')) {
      return trimmed.split(',').map((part) => part.trim());
    }

    return trimmed;
  }

  private resetDrafts(): void {
    this.selectedConditions = {};
    this.customConditionDrafts = {};
  }
}
