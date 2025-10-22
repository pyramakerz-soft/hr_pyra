import { CommonModule } from '@angular/common';
import { Component, Inject, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import Swal from 'sweetalert2';
import { UserServiceService } from '../../Services/user-service.service';
import {
  HrUserProfile,
  HrUserProfileUpdatePayload,
  HrVacationBalance,
  HrVacationTypeOption,
} from '../../Models/hr-user-profile';

interface DialogData {
  userId: number;
}

@Component({
  selector: 'app-employee-hr-profile-dialog',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MatDialogModule],
  templateUrl: './employee-hr-profile-dialog.component.html',
  styleUrl: './employee-hr-profile-dialog.component.css',
})
export class EmployeeHrProfileDialogComponent implements OnInit {
  profile: HrUserProfile | null = null;
  isLoading = false;
  isSaving = false;
  form: FormGroup;
  balancesToDelete: number[] = [];
  vacationTypeOptions: HrVacationTypeOption[] = [];
  loadError: string | null = null;
  readonly currentYear = new Date().getFullYear();

  constructor(
    private readonly fb: FormBuilder,
    private readonly userService: UserServiceService,
    private readonly dialogRef: MatDialogRef<EmployeeHrProfileDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
  ) {
    this.form = this.fb.group({
      detail: this.fb.group({
        salary: [''],
        hourly_rate: [''],
        overtime_hourly_rate: [''],
        working_hours_day: [''],
        overtime_hours: [''],
        start_time: [''],
        end_time: [''],
        emp_type: [''],
        hiring_date: [''],
      }),
      balances: this.fb.array([]),
    });
  }

  ngOnInit(): void
  {
    this.fetchProfile();
  }

  get detailGroup(): FormGroup
  {
    return this.form.get('detail') as FormGroup;
  }

  get balances(): FormArray
  {
    return this.form.get('balances') as FormArray;
  }

  fetchProfile(): void
  {
    this.isLoading = true;
    this.loadError = null;

    this.userService.getHrUserProfile(this.data.userId).subscribe({
      next: (response) => {
        this.profile = response.profile;
        this.vacationTypeOptions = response.profile.vacation_types ?? [];
        this.patchDetail(response.profile.detail);
        this.setBalances(response.profile.vacation_balances ?? []);
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.loadError = 'Unable to load employee profile.';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Unable to load employee profile. Please try again later.',
          confirmButtonColor: '#FF7519',
        }).then(() => this.dialogRef.close());
      },
    });
  }

  patchDetail(detail: HrUserProfile['detail']): void
  {
    this.detailGroup.patchValue({
      salary: detail?.salary ?? '',
      hourly_rate: detail?.hourly_rate ?? '',
      overtime_hourly_rate: detail?.overtime_hourly_rate ?? '',
      working_hours_day: detail?.working_hours_day ?? '',
      overtime_hours: detail?.overtime_hours ?? '',
      start_time: detail?.start_time ?? '',
      end_time: detail?.end_time ?? '',
      emp_type: detail?.emp_type ?? '',
      hiring_date: detail?.hiring_date ?? '',
    });
  }

  setBalances(balances: HrVacationBalance[]): void
  {
    this.balances.clear();
    balances.forEach(balance => {
      this.balances.push(this.createBalanceGroup(balance));
    });
  }

  addBalance(): void
  {
    this.balances.push(this.createBalanceGroup());
  }

  removeBalance(index: number): void
  {
    const group = this.balances.at(index) as FormGroup;
    const id = group.get('id')?.value;
    if (id) {
      this.balancesToDelete.push(id);
    }
    this.balances.removeAt(index);
  }

  private createBalanceGroup(balance?: HrVacationBalance): FormGroup
  {
    const group = this.fb.group({
      id: [balance?.id ?? null],
      vacation_type_id: [balance?.vacation_type_id ?? null, Validators.required],
      year: [balance?.year ?? this.currentYear, [Validators.required]],
      allocated_days: [balance?.allocated_days ?? 0, [Validators.required, Validators.min(0)]],
      used_days: [balance?.used_days ?? 0, [Validators.min(0)]],
      remaining_days: [{ value: balance?.remaining_days ?? 0, disabled: true }],
    });

    group.valueChanges.subscribe(() => {
      const allocated = parseFloat(group.get('allocated_days')?.value ?? 0);
      const usedRaw = group.get('used_days')?.value;
      const used = usedRaw === '' || usedRaw === null ? 0 : parseFloat(usedRaw);
      const remaining = Number.isFinite(allocated - used) ? allocated - used : 0;
      group.get('remaining_days')?.setValue(remaining, { emitEvent: false });
    });

    return group;
  }

  getVacationTypeName(typeId: number | null | undefined): string
  {
    if (!typeId) {
      return '';
    }

    const match = this.vacationTypeOptions.find(option => option.id === typeId);
    return match ? match.name : '';
  }

  availableVacationTypes(index: number): HrVacationTypeOption[]
  {
    const selectedIds = this.balances.controls
      .filter((_, i) => i !== index)
      .map(control => control.get('vacation_type_id')?.value)
      .filter((id): id is number => !!id);

    return this.vacationTypeOptions.filter(option => !selectedIds.includes(option.id));
  }

  save(): void
  {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      Swal.fire({
        icon: 'warning',
        title: 'Incomplete',
        text: 'Please review the highlighted fields before saving.',
        confirmButtonColor: '#FF7519',
      });
      return;
    }

    const payload = this.buildPayload();

    this.isSaving = true;
    this.userService.updateHrUserProfile(this.data.userId, payload).subscribe({
      next: (response) => {
        this.isSaving = false;
        this.balancesToDelete = [];
        this.profile = response.profile;
        this.patchDetail(response.profile.detail);
        this.setBalances(response.profile.vacation_balances ?? []);
        Swal.fire({
          icon: 'success',
          title: 'Updated',
          text: 'Employee profile updated successfully.',
          confirmButtonColor: '#FF7519',
        });
      },
      error: () => {
        this.isSaving = false;
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Could not update the employee profile. Please try again.',
          confirmButtonColor: '#FF7519',
        });
      },
    });
  }

  close(): void
  {
    this.dialogRef.close();
  }

  private buildPayload(): HrUserProfileUpdatePayload
  {
    const detailRaw = this.detailGroup.getRawValue();
    const detailPayload: Record<string, unknown> = {};

    const numericFields = ['salary', 'hourly_rate', 'overtime_hourly_rate', 'working_hours_day', 'overtime_hours'];
    Object.entries(detailRaw).forEach(([key, value]) => {
      if (value === '' || value === null || value === undefined) {
        detailPayload[key] = null;
        return;
      }

      if (numericFields.includes(key)) {
        const numericValue = Number(value);
        detailPayload[key] = Number.isFinite(numericValue) ? numericValue : null;
        return;
      }

      detailPayload[key] = typeof value === 'string' ? value.trim() : value;
    });

    const balancesPayload = this.balances.controls.map(control => {
      const raw = control.getRawValue();
      return {
        id: raw.id ?? undefined,
        vacation_type_id: Number(raw.vacation_type_id),
        year: raw.year ? Number(raw.year) : this.currentYear,
        allocated_days: Number(raw.allocated_days ?? 0),
        used_days: raw.used_days === '' || raw.used_days === null ? 0 : Number(raw.used_days),
      };
    }).filter(balance => balance.vacation_type_id);

    const payload: HrUserProfileUpdatePayload = {};

    if (Object.keys(detailPayload).length > 0) {
      payload.detail = detailPayload;
    }

    if (balancesPayload.length > 0) {
      payload.vacation_balances = balancesPayload;
    }

    if (this.balancesToDelete.length > 0) {
      payload.vacation_balance_ids_to_delete = this.balancesToDelete.slice();
    }

    return payload;
  }
}
