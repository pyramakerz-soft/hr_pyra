import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DeductionPlanService } from '../../../Services/deduction-plan.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { DeductionPlan, DeductionRule } from '../../../Models/deduction-plan';
import { Department } from '../../../Models/department';
import { ApiService } from '../../../Services/api.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-deductions',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-deductions.component.html',
  styleUrl: './hr-deductions.component.css'
})
export class HrDeductionsComponent implements OnInit {
  departments: Department[] = [];
  subDepartments: any[] = [];
  selectedType: 'department' | 'sub-department' = 'department';
  selectedId: number | null = null;
  
  plan: DeductionPlan = {
    grace_minutes: 0,
    rules: [],
    overwrite: false
  };

  templates: any[] = [];
  selectedTemplate: any = null;

  constructor(
    private deductionService: DeductionPlanService,
    private departmentService: DepartmentService,
    private subDepartmentService: SubDepartmentService,
    private apiService: ApiService
  ) {}

  ngOnInit() {
    this.loadDepartments();
    this.loadTemplates();
  }

  loadTemplates() {
    this.deductionService.getTemplates().subscribe(templates => {
      this.templates = templates;
    });
  }

  loadDepartments() {
    this.departmentService.getall().subscribe((res: any) => {
      this.departments = res.data?.departments || res.data || [];
    });
  }

  onTypeChange() {
    this.selectedId = null;
    this.subDepartments = [];
    if (this.selectedType === 'department') {
      this.loadDepartments();
    }
  }

  onDepartmentForSubChange(deptId: any) {
    if (deptId) {
      this.subDepartmentService.getall(Number(deptId)).subscribe((res: any) => {
        this.subDepartments = res.data || [];
      });
    }
  }

  loadPlan() {
    if (!this.selectedId) return;

    const obs = this.selectedType === 'department' 
      ? this.deductionService.getDepartmentPlan(this.selectedId)
      : this.deductionService.getSubDepartmentPlan(this.selectedId);

    obs.subscribe({
      next: (plan: DeductionPlan) => {
        this.plan = plan;
      },
      error: () => {
        this.plan = { grace_minutes: 0, rules: [], overwrite: false };
      }
    });
  }

  addRule() {
    const newRule: DeductionRule = {
      label: 'New Rule',
      category: 'late_arrival',
      scope: 'occurrence',
      order: this.plan.rules.length,
      penalty: { type: 'fixed_minutes', value: 0 },
      when: {}
    };
    this.plan.rules.push(newRule);
  }

  applyTemplate(template: any) {
    if (!template) return;
    
    // Normalize rule to array if it's a single object
    const templateRules = Array.isArray(template.rule) ? template.rule : [template.rule];
    
    templateRules.forEach((rule: any) => {
      const newRule: DeductionRule = {
        label: rule.label || rule.name || 'Template Rule',
        category: rule.category || 'other',
        scope: rule.scope || 'occurrence',
        order: this.plan.rules.length,
        penalty: { 
          type: rule.penalty?.type || 'fixed_minutes', 
          value: rule.penalty?.value || 0 
        },
        when: { ...(rule.when || {}) },
        notes: rule.notes || null,
        stop_processing: !!rule.stop_processing
      };
      this.plan.rules.push(newRule);
    });
    
    this.selectedTemplate = null; // Reset selection
    Swal.fire('Template Applied', `${template.name} rules added to current plan.`, 'info');
  }

  removeRule(index: number) {
    this.plan.rules.splice(index, 1);
  }

  savePlan() {
    if (!this.selectedId) {
      Swal.fire('Error', 'Please select a department or sub-department', 'error');
      return;
    }

    const obs = this.selectedType === 'department'
      ? this.deductionService.saveDepartmentPlan(this.selectedId, this.plan)
      : this.deductionService.saveSubDepartmentPlan(this.selectedId, this.plan);

    obs.subscribe({
      next: (plan: DeductionPlan) => {
        this.plan = plan;
        Swal.fire('Success', 'Deduction plan saved successfully', 'success');
      },
      error: (err: any) => {
        Swal.fire('Error', err.error?.message || 'Failed to save plan', 'error');
      }
    });
  }
}
