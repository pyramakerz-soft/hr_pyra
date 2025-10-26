import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subscription } from 'rxjs';
import Swal from 'sweetalert2';
import { NotificationCenterService } from '../../../Services/notification-center.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { UserServiceService } from '../../../Services/user-service.service';
import { RolesService } from '../../../Services/roles.service';
import { Department } from '../../../Models/department';
import { SubDepartment } from '../../../Models/sub-department';
import { UserModel } from '../../../Models/user-model';
import { RoleModel } from '../../../Models/role-model';
import { SystemNotificationRecord } from '../../../Models/system-notification';

@Component({
  selector: 'app-hr-notifications',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, RouterLink],
  templateUrl: './hr-notifications.component.html',
  styleUrl: './hr-notifications.component.css'
})
export class HrNotificationsComponent implements OnInit, OnDestroy {
  notificationForm: FormGroup;
  notificationTypes: { value: string; label: string }[] = [];
  scopeOptions = [
    { key: 'all', label: 'Entire organization' },
    { key: 'department', label: 'Department' },
    { key: 'sub_department', label: 'Sub department' },
    { key: 'user', label: 'Single employee' },
    { key: 'custom', label: 'Selected employees' },
  ];

  departments: Department[] = [];
  subDepartments: SubDepartment[] = [];
  employees: UserModel[] = [];
  roles: RoleModel[] = [];
  notifications: SystemNotificationRecord[] = [];

  isSubmitting = false;
  private subscriptions: Subscription[] = [];

  constructor(
    private readonly fb: FormBuilder,
    private readonly notificationCenter: NotificationCenterService,
    private readonly departmentService: DepartmentService,
    private readonly subDepartmentService: SubDepartmentService,
    private readonly userService: UserServiceService,
    private readonly rolesService: RolesService
  ) {
    this.notificationForm = this.fb.group({
      type: ['', Validators.required],
      title: ['', [Validators.required, Validators.maxLength(190)]],
      message: ['', Validators.required],
      scope_type: ['all', Validators.required],
      scope_id: [''],
      user_ids: [[]],
      filters: this.fb.group({
        roles: [[]],
      }),
    });
  }

  ngOnInit(): void {
    this.loadNotificationTypes();
    this.loadDepartments();
    this.loadEmployees();
    this.loadRoles();
    this.loadNotifications();

    this.subscriptions.push(
      this.notificationForm.get('scope_type')?.valueChanges.subscribe(() => {
        this.notificationForm.patchValue(
          { scope_id: '', user_ids: [] },
          { emitEvent: false }
        );
      }) ?? new Subscription()
    );
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }

  onDepartmentChange(departmentId: string): void {
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
    if (this.notificationForm.invalid) {
      this.notificationForm.markAllAsTouched();
      return;
    }

    const formValue = this.notificationForm.value;

    const payload: any = {
      type: formValue.type,
      title: formValue.title,
      message: formValue.message,
      scope_type: formValue.scope_type,
    };

    if (formValue.scope_id) {
      payload.scope_id = Number(formValue.scope_id);
    }

    if (formValue.scope_type === 'custom') {
      payload.user_ids = formValue.user_ids ?? [];
    }

    const rolesFilter = formValue.filters?.roles ?? [];
    if (Array.isArray(rolesFilter) && rolesFilter.length > 0) {
      payload.filters = { roles: rolesFilter };
    }

    this.isSubmitting = true;
    this.notificationCenter.createNotification(payload).subscribe({
      next: () => {
        this.isSubmitting = false;
        this.notificationForm.patchValue({
          title: '',
          message: '',
        });
        this.loadNotifications();
        Swal.fire({
          icon: 'success',
          title: 'Notification sent',
          text: 'Your notification has been queued for delivery.',
          confirmButtonColor: '#437EF7',
        });
      },
      error: () => {
        this.isSubmitting = false;
        Swal.fire({
          icon: 'error',
          title: 'Notification failed',
          text: 'We were unable to dispatch the notification. Please try again later.',
          confirmButtonColor: '#F87171',
        });
      },
    });
  }

  trackById(_index: number, item: { id: number }): number {
    return item.id;
  }

  private loadNotificationTypes(): void {
    this.notificationCenter.getTypes().subscribe((response) => {
      const types = response?.types ?? {};
      this.notificationTypes = Object.entries(types).map(([value, label]) => ({
        value,
        label,
      }));

      if (this.notificationTypes.length > 0 && !this.notificationForm.get('type')?.value) {
        this.notificationForm.patchValue({ type: this.notificationTypes[0].value });
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

  private loadRoles(): void {
    this.rolesService.getall().subscribe((response: any) => {
      const list = response?.data?.roles ?? response;
      this.roles = Array.isArray(list) ? list : [];
    });
  }

  private loadNotifications(): void {
    this.notificationCenter.getNotifications(15).subscribe((response) => {
      this.notifications = response.notifications ?? [];
    });
  }
}

