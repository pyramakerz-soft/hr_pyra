import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, Inject, OnInit } from '@angular/core';
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
    private readonly cdr: ChangeDetectorRef,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
  ) {
    this.form = this.fb.group({
      detail: this.fb.group({
        working_hours_day: [''],
        start_time: [''],
        end_time: [''],
        emp_type: [''],
        hiring_date: [''],
      }),
      balances: this.fb.array([]),
    });
  }

  ngOnInit(): void {
    this.fetchProfile();
  }

  get detailGroup(): FormGroup {
    return this.form.get('detail') as FormGroup;
  }

  get balances(): FormArray {
    return this.form.get('balances') as FormArray;
  }

  fetchProfile(): void {
    this.isLoading = true;
    this.loadError = null;

    this.userService.getHrUserProfile(this.data.userId).subscribe({
      next: (response) => {
        this.profile = response.profile;
        this.vacationTypeOptions = response.profile.vacation_types ?? [];
        this.patchDetail(response.profile.detail);
        this.setBalances(response.profile.vacation_balances ?? []);
        this.isLoading = false;
        
        // Manually trigger change detection to ensure the view updates
        this.cdr.detectChanges();
      },
      error: () => {
        this.isLoading = false;
        this.loadError = 'Unable to load employee profile.';
        this.cdr.detectChanges();
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Unable to load employee profile. Please try again later.',
          confirmButtonColor: '#FF7519',
        }).then(() => this.dialogRef.close());
      },
    });
  }

  patchDetail(detail: HrUserProfile['detail']): void {
    const startTime = this.formatTimeForInput(detail?.start_time);
    const endTime = this.formatTimeForInput(detail?.end_time);
    const hiringDate = this.formatDateForInput(detail?.hiring_date);

    this.detailGroup.patchValue({
      working_hours_day: detail?.working_hours_day ?? '',
      start_time: startTime,
      end_time: endTime,
      emp_type: detail?.emp_type ?? '',
      hiring_date: hiringDate,
    });
  }

  setBalances(balances: HrVacationBalance[]): void {
    this.balances.clear();
    
    const currentYearBalances = (balances ?? []).filter(
      (balance) => (balance.year ?? this.currentYear) === this.currentYear,
    );

    currentYearBalances.forEach((balance) => {
      this.balances.push(this.createBalanceGroup(balance));
    });
    
    this.cdr.detectChanges();
  }

addBalance(): void {
  const availableTypes = this.availableVacationTypes();
  if (availableTypes.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'All Types Added',
      text: 'All vacation types already have balances for this year.',
      confirmButtonColor: '#FF7519',
    });
    return;
  }

  const nextType = availableTypes[0];
  const newBalanceGroup = this.createBalanceGroup({
    vacation_type_id: nextType.id,
    vacation_type_name: nextType.name,
    year: this.currentYear,
    allocated_days: 0,
    used_days: 0,
    remaining_days: 0,
  } as HrVacationBalance);
  
  this.balances.push(newBalanceGroup);
  
  // Mark the form as touched to show validation immediately
  newBalanceGroup.markAllAsTouched();
  
  // Force change detection
  this.cdr.detectChanges();
}

  removeBalance(index: number): void {
    const group = this.balances.at(index) as FormGroup;
    const id = group.get('id')?.value;
    if (id) {
      this.balancesToDelete.push(id);
    }
    this.balances.removeAt(index);
  }

  availableVacationTypes(excludeIndex: number | null = null): HrVacationTypeOption[] {
    const selectedIds = this.balances.controls
      .filter((_, index) => index !== excludeIndex)
      .map((control) => control.get('vacation_type_id')?.value)
      .filter((id): id is number => !!id);

    return this.vacationTypeOptions.filter((option) => !selectedIds.includes(option.id));
  }

  getVacationTypeName(vacationTypeId: number | null | undefined): string {
    if (!vacationTypeId) {
      return '';
    }

    const match = this.vacationTypeOptions.find((option) => option.id === vacationTypeId);
    return match ? match.name : '';
  }
  
  canAddMoreBalances(): boolean {
    return this.availableVacationTypes().length > 0;
  }

save(): void {
  // Validate all form controls
  this.validateAllFormControls();
  
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
  
  // Log payload for debugging
  console.log('Saving payload:', payload);

  this.isSaving = true;
  this.userService.updateHrUserProfile(this.data.userId, payload).subscribe({
    next: (response) => {
      this.isSaving = false;
      this.balancesToDelete = [];
      this.profile = response.profile;
      this.patchDetail(response.profile.detail);
      this.setBalances(response.profile.vacation_balances ?? []);
      this.cdr.detectChanges();
      
      Swal.fire({
        icon: 'success',
        title: 'Updated',
        text: 'Employee profile updated successfully.',
        confirmButtonColor: '#FF7519',
      });
    },
    error: (error) => {
      this.isSaving = false;
      this.cdr.detectChanges();
      
      console.error('Save error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Could not update the employee profile. Please try again.',
        confirmButtonColor: '#FF7519',
      });
    },
  });
}

private validateAllFormControls(): void {
  // Validate each balance group
  this.balances.controls.forEach((control) => {
    if (control instanceof FormGroup) {
      Object.keys(control.controls).forEach((key) => {
        const formControl = control.get(key);
        formControl?.updateValueAndValidity();
      });
    }
  });
}

  close(): void {
    this.dialogRef.close();
  }

private createBalanceGroup(balance?: HrVacationBalance): FormGroup {
  const group = this.fb.group({
    id: [balance?.id ?? null],
    vacation_type_id: [balance?.vacation_type_id ?? null, [Validators.required, Validators.min(1)]],
    allocated_days: [balance?.allocated_days ?? 0, [Validators.required, Validators.min(0)]],
    used_days: [balance?.used_days ?? 0, [Validators.min(0)]],
    remaining_days: [{ value: balance?.remaining_days ?? 0, disabled: true }],
    year: [{ value: balance?.year ?? this.currentYear, disabled: true }],
  });

  // Calculate remaining days when values change
  group.valueChanges.subscribe(() => {
    const allocated = Number(group.get('allocated_days')?.value ?? 0);
    const used = Number(group.get('used_days')?.value ?? 0);
    const remaining = Math.max(0, allocated - used);
    group.get('remaining_days')?.setValue(remaining, { emitEvent: false });
  });

  // Calculate initial remaining days
  const allocated = Number(group.get('allocated_days')?.value ?? 0);
  const used = Number(group.get('used_days')?.value ?? 0);
  const remaining = Math.max(0, allocated - used);
  group.get('remaining_days')?.setValue(remaining, { emitEvent: false });

  return group;
}

private buildPayload(): HrUserProfileUpdatePayload {
  const detailRaw = this.detailGroup.getRawValue();
  const detailPayload: Record<string, unknown> = {};

  const numericFields = ['working_hours_day'];
  const timeFields = ['start_time', 'end_time'];
  const dateFields = ['hiring_date'];

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

    if (timeFields.includes(key)) {
      detailPayload[key] = this.formatTimeForPayload(String(value));
      return;
    }

    if (dateFields.includes(key)) {
      detailPayload[key] = this.formatDateForPayload(String(value));
      return;
    }

    detailPayload[key] = typeof value === 'string' ? value.trim() : value;
  });

  // Build vacation balances payload
  const balancesPayload = this.balances.controls
    .map((control) => {
      const raw = control.getRawValue();
      
      // Skip if vacation_type_id is not valid
      if (!raw.vacation_type_id || raw.vacation_type_id <= 0) {
        return null;
      }
      
      return {
        id: raw.id && raw.id > 0 ? raw.id : undefined,
        vacation_type_id: Number(raw.vacation_type_id),
        year: this.currentYear,
        allocated_days: Number(raw.allocated_days ?? 0),
        used_days: Number(raw.used_days ?? 0),
      };
    })
    .filter((balance): balance is NonNullable<typeof balance> => balance !== null);

  const payload: HrUserProfileUpdatePayload = {};

  // Only add detail if it has values
  if (Object.keys(detailPayload).length > 0) {
    payload.detail = detailPayload;
  }

  // Only add vacation_balances if there are any
  if (balancesPayload.length > 0) {
    payload.vacation_balances = balancesPayload;
  }

  // Add ids to delete if any
  if (this.balancesToDelete.length > 0) {
    payload.vacation_balance_ids_to_delete = this.balancesToDelete.slice();
  }

  return payload;
}
  private formatTimeForInput(value?: string | null): string {
    if (!value) {
      return '';
    }

    const trimmed = String(value).trim();
    if (trimmed === '') {
      return '';
    }

    const meridiemMatch = trimmed.match(/\s*(AM|PM)$/i);
    const meridiem = meridiemMatch ? meridiemMatch[1].toUpperCase() : null;
    const timePart = meridiemMatch ? trimmed.slice(0, meridiemMatch.index).trim() : trimmed;

    const [hoursStr, minutesStr] = timePart.split(':');
    if (!hoursStr || !minutesStr) {
      return trimmed;
    }

    let hours = parseInt(hoursStr, 10);
    const minutes = minutesStr.padStart(2, '0');

    if (meridiem === 'PM' && hours < 12) {
      hours += 12;
    }
    if (meridiem === 'AM' && hours === 12) {
      hours = 0;
    }

    return `${hours.toString().padStart(2, '0')}:${minutes}`;
  }

  private formatTimeForPayload(value?: string | null): string | null {
    if (!value) {
      return null;
    }

    const formatted = this.formatTimeForInput(value);
    return formatted ? formatted : null;
  }

  private formatDateForInput(value?: string | null): string {
    if (!value) {
      return '';
    }

    const trimmed = String(value).trim();
    if (trimmed === '') {
      return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
      return trimmed;
    }

    const normalized = trimmed.replace(/\//g, '-');
    const parts = normalized.split('-');

    if (parts.length === 3) {
      if (parts[0].length === 4) {
        const [year, month, day] = parts;
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
      }

      const [month, day, year] = parts;
      return `${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    const parsed = new Date(trimmed);
    if (!Number.isNaN(parsed.getTime())) {
      const year = parsed.getFullYear();
      const month = (parsed.getMonth() + 1).toString().padStart(2, '0');
      const day = parsed.getDate().toString().padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    return trimmed;
  }

  private formatDateForPayload(value?: string | null): string | null {
    if (!value) {
      return null;
    }

    const formatted = this.formatDateForInput(value);
    return /^\d{4}-\d{2}-\d{2}$/.test(formatted) ? formatted : null;
  }
}