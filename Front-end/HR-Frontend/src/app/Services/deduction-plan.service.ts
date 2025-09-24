import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { ApiService } from './api.service';
import { DeductionPlan, DeductionRule, ResolvedDeductionPlan } from '../Models/deduction-plan';
import { clonePlan, cloneRule } from '../Helpers/deduction-plan-editor';

@Injectable({ providedIn: 'root' })
export class DeductionPlanService {
  private baseUrl: string;

  constructor(private http: HttpClient, private api: ApiService) {
    this.baseUrl = `${this.api.BaseUrl}/deduction-plans`;
  }

  getDepartmentPlan(departmentId: number): Observable<DeductionPlan> {
    return this.http
      .get<any>(`${this.baseUrl}/department/${departmentId}`, { headers: this.getHeaders() })
      .pipe(map((response) => this.extractPlan(response)));
  }

  saveDepartmentPlan(departmentId: number, plan: DeductionPlan): Observable<DeductionPlan> {
    return this.http
      .post<any>(`${this.baseUrl}/department/${departmentId}`, this.preparePayload(plan, false), {
        headers: this.getHeaders(),
      })
      .pipe(map((response) => this.extractPlan(response)));
  }

  getSubDepartmentPlan(subDepartmentId: number): Observable<DeductionPlan> {
    return this.http
      .get<any>(`${this.baseUrl}/sub-department/${subDepartmentId}`, { headers: this.getHeaders() })
      .pipe(map((response) => this.extractPlan(response)));
  }

  saveSubDepartmentPlan(subDepartmentId: number, plan: DeductionPlan): Observable<DeductionPlan> {
    return this.http
      .post<any>(`${this.baseUrl}/sub-department/${subDepartmentId}`, this.preparePayload(plan), {
        headers: this.getHeaders(),
      })
      .pipe(map((response) => this.extractPlan(response)));
  }

  getUserPlan(userId: number): Observable<{ plan: DeductionPlan; effective_plan?: ResolvedDeductionPlan }> {
    return this.http
      .get<any>(`${this.baseUrl}/user/${userId}`, { headers: this.getHeaders() })
      .pipe(
        map((response) => {
          const plan = this.extractPlan(response);
          const effective = this.extractPlan<ResolvedDeductionPlan>(response, 'effective_plan');
          return {
            plan,
            effective_plan: effective,
          };
        })
      );
  }

  saveUserPlan(userId: number, plan: DeductionPlan): Observable<DeductionPlan> {
    return this.http
      .post<any>(`${this.baseUrl}/user/${userId}`, this.preparePayload(plan), { headers: this.getHeaders() })
      .pipe(map((response) => this.extractPlan(response)));
  }

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }

  private extractPlan<T extends DeductionPlan = DeductionPlan>(response: any, key: 'plan' | 'effective_plan' = 'plan'): T {
    const rawPlan = response?.data?.[key] ?? response?.[key] ?? {};
    const normalized = clonePlan(rawPlan);

    normalized.grace_minutes = this.normalizeNumber(normalized.grace_minutes);

    normalized.rules = (normalized.rules ?? []).map((rule, index) => this.normalizeRule(rule, index));
    normalized.rules.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));

    const result: any = { ...normalized };

    if (rawPlan?.sources) {
      result.sources = rawPlan.sources;
    }

    return result as T;
  }

  private normalizeRule(rawRule: DeductionRule | any, fallbackOrder: number): DeductionRule {
    const cloned = cloneRule(rawRule);

    const normalizedOrder = this.normalizeNumber(cloned.order);
    cloned.order = normalizedOrder === null ? fallbackOrder : Math.max(0, Math.floor(normalizedOrder));

    cloned.when = this.cloneWhen(cloned.when);

    cloned.penalty = {
      ...cloned.penalty,
      value: this.normalizeNumber(cloned.penalty?.value),
    };

    cloned.color = this.ensureColorPrefix(cloned.color);

    return cloned;
  }

  private cloneWhen(when: { [key: string]: any } = {}): { [key: string]: any } {
    const cloned: { [key: string]: any } = {};
    Object.keys(when).forEach((key) => {
      const value = when[key];
      cloned[key] = Array.isArray(value) ? [...value] : value;
    });
    return cloned;
  }

  private preparePayload(plan: DeductionPlan, supportsOverwrite = true): any {
    const normalizedRules = (plan.rules ?? []).map((rule, index) => this.prepareRule(rule, index));
    const payload: any = {
      grace_minutes: this.normalizeNumber(plan.grace_minutes),
      rules: normalizedRules,
    };

    if (supportsOverwrite) {
      payload.overwrite = !!plan.overwrite;
    }

    return payload;
  }

  private prepareRule(rule: DeductionRule, index: number): any {
    const sanitizedWhen: { [key: string]: any } = {};
    const when = rule.when ?? {};

    Object.keys(when).forEach((key) => {
      const value = this.normalizeWhenValue(when[key]);
      if (value !== null && value !== '') {
        sanitizedWhen[key] = Array.isArray(value) ? [...value] : value;
      }
    });

    return {
      label: rule.label ?? '',
      category: rule.category ?? 'other',
      scope: rule.scope ?? 'occurrence',
      order: index,
      stop_processing: !!rule.stop_processing,
      notes: rule.notes ?? null,
      color: rule.color ? this.normalizeColor(rule.color) : null,
      when: sanitizedWhen,
      penalty: {
        type: rule.penalty?.type ?? 'fixed_minutes',
        value: this.normalizeNumber(rule.penalty?.value),
        unit: rule.penalty?.unit ?? null,
        meta: rule.penalty?.meta ?? null,
      },
      meta: rule.meta ?? null,
    };
  }

  private normalizeNumber(value: any): number | null {
    if (value === '' || value === null || value === undefined) {
      return null;
    }
    const numeric = Number(value);
    return Number.isNaN(numeric) ? null : numeric;
  }

  private normalizeWhenValue(value: any): any {
    if (value === '' || value === null || value === undefined) {
      return null;
    }

    if (Array.isArray(value)) {
      return value.map((item) => (typeof item === 'string' ? item.trim() : item));
    }

    if (typeof value === 'boolean') {
      return value;
    }

    if (value === 'true' || value === 'false') {
      return value === 'true';
    }

    const numeric = Number(value);
    return Number.isNaN(numeric) ? value : numeric;
  }

  private ensureColorPrefix(color: string | null | undefined): string | null {
    if (!color) {
      return null;
    }

    const trimmed = color.trim();
    const hex = trimmed.startsWith('#') ? trimmed.substring(1) : trimmed;
    if (!hex) {
      return null;
    }

    return `#${hex.toUpperCase()}`;
  }

  private normalizeColor(color: string): string {
    const trimmed = color.trim();
    const cleaned = trimmed.startsWith('#') ? trimmed.substring(1) : trimmed;
    return cleaned.toUpperCase();
  }
}
