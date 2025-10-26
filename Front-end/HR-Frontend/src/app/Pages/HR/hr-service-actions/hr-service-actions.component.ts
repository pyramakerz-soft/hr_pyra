import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Subscription } from 'rxjs';
import Swal from 'sweetalert2';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { UserServiceService } from '../../../Services/user-service.service';
import { ServiceActionService } from '../../../Services/service-action.service';
import { Department } from '../../../Models/department';
import { SubDepartment } from '../../../Models/sub-department';
import { UserModel } from '../../../Models/user-model';
import {
  ServiceActionDefinition,
  ServiceActionRecord,
  ServiceActionOptionField,
} from '../../../Models/service-action';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-hr-service-actions',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, RouterLink],
  templateUrl: './hr-service-actions.component.html',
  styleUrl: './hr-service-actions.component.css'
})
export class HrServiceActionsComponent implements OnInit, OnDestroy {
  actionDefinitions: ServiceActionDefinition[] = [];
  scopeOptions: { key: string; label: string }[] = [];
  actionForm: FormGroup;
  departments: Department[] = [];
  subDepartments: SubDepartment[] = [];
  employees: UserModel[] = [];
  recentActions: ServiceActionRecord[] = [];
  executionResult: ServiceActionRecord | null = null;
  isSubmitting = false;
  isLoadingHistory = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private readonly fb: FormBuilder,
    private readonly serviceActionService: ServiceActionService,
    private readonly departmentService: DepartmentService,
    private readonly subDepartmentService: SubDepartmentService,
    private readonly userService: UserServiceService
  ) {
    this.actionForm = this.fb.group({
      action_type: ['', Validators.required],
      scope_type: ['all', Validators.required],
      scope_id: [''],
      user_ids: [[]],
      payload: this.fb.group({
        date: [''],
        clock_out_time: [''],
        default_duration_minutes: [540],
        from_date: [''],
        to_date: [''],
      }),
    });
  }

  ngOnInit(): void {
    this.loadDefinitions();
    this.loadDepartments();
    this.loadEmployees();
    this.refreshHistory();

    this.subscriptions.push(
      this.actionForm.get('scope_type')?.valueChanges.subscribe(() => {
        this.actionForm.patchValue(
          { scope_id: '', user_ids: [] },
          { emitEvent: false }
        );
      }) ?? new Subscription()
    );

    this.subscriptions.push(
      this.actionForm.get('action_type')?.valueChanges.subscribe(() => {
        this.resetPayloadDefaults();
      }) ?? new Subscription()
    );
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }

  get selectedDefinition(): ServiceActionDefinition | undefined {
    const actionType = this.actionForm.get('action_type')?.value;
    return this.actionDefinitions.find((definition) => definition.key === actionType);
  }

  get payloadFields(): ServiceActionOptionField[] {
    return this.selectedDefinition?.payload_fields ?? [];
  }

  get scopeType(): string {
    return this.actionForm.get('scope_type')?.value ?? '';
  }

  onDepartmentChange(event: Event): void {
    const select = event.target as HTMLSelectElement | null;
    const departmentId = select?.value ?? '';

    if (!departmentId) {
      this.subDepartments = [];
      return;
    }

    const deptId = Number(departmentId);
    if (Number.isNaN(deptId)) {
      this.subDepartments = [];
      return;
    }

    this.subDepartmentService.setDeptId(deptId);
    this.subDepartmentService.getall(deptId).subscribe((response: any) => {
      const list = response?.data?.sub_departments ?? response;
      this.subDepartments = Array.isArray(list) ? list : [];
    });
  }

  submit(): void {
    if (this.actionForm.invalid || !this.selectedDefinition) {
      this.actionForm.markAllAsTouched();
      return;
    }

    const formValue = this.actionForm.value;

    const payload: any = {
      action_type: formValue.action_type,
      scope_type: formValue.scope_type,
    };

    if (formValue.scope_id) {
      payload.scope_id = Number(formValue.scope_id);
    }

    if (formValue.scope_type === 'custom') {
      payload.user_ids = formValue.user_ids ?? [];
    }

    const payloadData: any = {};
    this.payloadFields.forEach((field) => {
      const value = formValue.payload?.[field.key];
      if (value !== null && value !== undefined && value !== '') {
        payloadData[field.key] = value;
      }
    });

    if (Object.keys(payloadData).length > 0) {
      payload.payload = payloadData;
    }

    this.isSubmitting = true;
    this.serviceActionService.execute(payload).subscribe({
      next: (response) => {
        this.executionResult = response.service_action;
        this.isSubmitting = false;
        this.refreshHistory();
        Swal.fire({
          icon: 'success',
          title: 'Action executed',
          text: 'The queued service action completed successfully.',
          confirmButtonColor: '#437EF7',
        });
      },
      error: () => {
        this.isSubmitting = false;
        Swal.fire({
          icon: 'error',
          title: 'Action failed',
          text: 'We were unable to complete the requested action. Review the configuration and try again.',
          confirmButtonColor: '#F87171',
        });
      },
    });
  }

  formatResult(action: ServiceActionRecord): string {
    if (!action.result) {
      return 'No additional details recorded.';
    }

    if (action.action_type === 'force_clock_out' && typeof action.result.affected_clocks === 'number') {
      return `${action.result.affected_clocks} open clocks closed`;
    }

    if (action.action_type === 'resolve_clock_issues' && typeof action.result.resolved_issues === 'number') {
      return `${action.result.resolved_issues} issues resolved`;
    }

    if (action.action_type === 'recompute_durations' && typeof action.result.recomputed === 'number') {
      return `${action.result.recomputed} duration entries recalculated`;
    }

    return 'Completed';
  }

  private loadDefinitions(): void {
    this.serviceActionService.getAvailable().subscribe((response) => {
      this.actionDefinitions = response.actions.definitions ?? [];
      this.scopeOptions = response.actions.scopes ?? [];

      if (this.actionDefinitions.length > 0 && !this.actionForm.get('action_type')?.value) {
        this.actionForm.patchValue({ action_type: this.actionDefinitions[0].key });
      }
    });
  }

  private loadDepartments(): void {
    this.departmentService.getall().subscribe((response: any) => {
      const list = response?.data?.departments ?? response;
      this.departments = Array.isArray(list) ? list : [];
    });
  }

  private loadEmployees(): void {
    this.userService.getAllUsersName().subscribe((response: any) => {
      const list = response?.usersNames ?? response?.data?.usersNames ?? [];
      this.employees = Array.isArray(list) ? list : [];
    });
  }

  refreshHistory(): void {
    this.isLoadingHistory = true;
    this.serviceActionService.getRecent().subscribe({
      next: (response) => {
        this.recentActions = response.service_actions ?? [];
        this.isLoadingHistory = false;
      },
      error: () => {
        this.recentActions = [];
        this.isLoadingHistory = false;
      },
    });
  }

  private resetPayloadDefaults(): void {
    const payloadGroup = this.actionForm.get('payload');
    if (!payloadGroup) {
      return;
    }

    payloadGroup.patchValue(
      {
        date: '',
        clock_out_time: '',
        default_duration_minutes: 540,
        from_date: '',
        to_date: '',
      },
      { emitEvent: false }
    );
  }

  trackById(_index: number, item: { id: number }): number {
    return item.id;
  }
}
