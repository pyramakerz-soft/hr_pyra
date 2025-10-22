import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { forkJoin, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { CustomVacation, CustomVacationPayload } from '../../../Models/custom-vacation';
import { CustomVacationService } from '../../../Services/custom-vacation.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';

type BasicSubDepartment = {
  id: number;
  name: string;
  department_id: number;
};

@Component({
  selector: 'app-hr-custom-vacations',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './hr-custom-vacations.component.html',
  styleUrl: './hr-custom-vacations.component.css'
})
export class HrCustomVacationsComponent implements OnInit {

  vacationForm: FormGroup;
  vacations: CustomVacation[] = [];
  departments: any[] = [];
  availableSubDepartments: BasicSubDepartment[] = [];
  private subDepartmentsByDept = new Map<number, BasicSubDepartment[]>();

  isLoading = false;
  listError: string | null = null;
  formError: string | null = null;
  saveInProgress = false;
  isEditing = false;
  editingVacationId: number | null = null;
  loadingSubDepartments = false;

  pagination = {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  };

  constructor(
    private fb: FormBuilder,
    private customVacationService: CustomVacationService,
    private departmentService: DepartmentService,
    private subDepartmentService: SubDepartmentService
  ) {
    this.vacationForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      start_date: ['', Validators.required],
      end_date: ['', Validators.required],
      is_full_day: [true],
      description: [''],
      department_ids: [[]],
      sub_department_ids: [[]],
    });
  }

  ngOnInit(): void {
    this.loadDepartments();
    this.loadVacations();
    this.vacationForm.get('department_ids')?.valueChanges.subscribe((ids: any[]) => {
      this.handleDepartmentSelection(ids || []);
    });
  }

  loadVacations(page: number = 1): void {
    this.isLoading = true;
    this.customVacationService.getVacations({ page, per_page: this.pagination.per_page }).subscribe({
      next: (response) => {
        const payload = response?.vacations;
        this.vacations = payload?.data ?? [];
        this.pagination.current_page = payload?.current_page ?? 1;
        this.pagination.last_page = payload?.last_page ?? 1;
        this.pagination.per_page = payload?.per_page ?? this.pagination.per_page;
        this.pagination.total = payload?.total ?? this.vacations.length;
        this.isLoading = false;
        this.listError = null;
      },
      error: () => {
        this.isLoading = false;
        this.listError = 'Unable to load custom vacations. Please try again.';
      }
    });
  }

  loadDepartments(): void {
    this.departmentService.getall().subscribe({
      next: (response: any) => {
        const departments = response?.data?.departments ?? response;
        this.departments = Array.isArray(departments) ? departments : [];
      },
      error: () => {
        this.departments = [];
      }
    });
  }

  private handleDepartmentSelection(rawIds: any[]): void {
    const departmentIds = this.toNumberArray(rawIds);
    this.loadSubDepartmentsForSelectedDepartments(departmentIds);
  }

  private loadSubDepartmentsForSelectedDepartments(departmentIds: number[], presetSubDeptIds: number[] = []): void {
    const uniqueDeptIds = Array.from(new Set(departmentIds.filter(id => !isNaN(id))));

    if (!uniqueDeptIds.length) {
      this.availableSubDepartments = [];
      this.vacationForm.get('sub_department_ids')?.patchValue([], { emitEvent: false });
      return;
    }

    const missingIds = uniqueDeptIds.filter(id => !this.subDepartmentsByDept.has(id));

    if (!missingIds.length) {
      this.refreshAvailableSubDepartments(uniqueDeptIds, presetSubDeptIds);
      return;
    }

    this.loadingSubDepartments = true;

    forkJoin(
      missingIds.map(id => {
        this.subDepartmentService.setDeptId(id);
        return this.subDepartmentService.getall(id).pipe(
          map((response: any) => {
            const collection = Array.isArray(response) ? response : (response?.data ?? []);
            return collection.map((item: any) => ({
              id: Number(item.id),
              name: item.name,
              department_id: Number(item.department_id ?? id),
            }) as BasicSubDepartment);
          }),
          map(subDepartments => ({ id, subDepartments }))
        );
      })
    ).pipe(
      catchError(() => {
        this.loadingSubDepartments = false;
        return of([]);
      })
    ).subscribe((results: Array<{ id: number; subDepartments: BasicSubDepartment[] }>) => {
      results.forEach(({ id, subDepartments }) => this.subDepartmentsByDept.set(id, subDepartments));
      this.refreshAvailableSubDepartments(uniqueDeptIds, presetSubDeptIds);
      this.loadingSubDepartments = false;
    });
  }

  private refreshAvailableSubDepartments(departmentIds: number[], presetSubDeptIds: number[] = []): void {
    const merged = departmentIds.flatMap(id => this.subDepartmentsByDept.get(id) ?? []);
    const unique = new Map<number, BasicSubDepartment>();
    merged.forEach(subDept => {
      unique.set(subDept.id, subDept);
    });
    this.availableSubDepartments = Array.from(unique.values());

    const control = this.vacationForm.get('sub_department_ids');
    if (!control) {
      return;
    }

    const currentSelection = this.toNumberArray(control.value ?? []);
    const target = presetSubDeptIds.length ? presetSubDeptIds : currentSelection;
    const allowedIds = new Set(this.availableSubDepartments.map(sd => sd.id));
    const filtered = target.filter(id => allowedIds.has(id));

    if (!this.arraysEqual(filtered, currentSelection)) {
      control.patchValue(filtered, { emitEvent: false });
    }
  }

  submitForm(): void {
    this.formError = null;
    if (this.vacationForm.invalid) {
      this.vacationForm.markAllAsTouched();
      this.formError = 'Please fill in the required fields.';
      return;
    }

    const { start_date, end_date } = this.vacationForm.value;
    if (start_date && end_date && new Date(start_date) > new Date(end_date)) {
      this.formError = 'End date cannot be before start date.';
      return;
    }

    const payload = this.buildPayload();
    this.saveInProgress = true;

    const request$ = this.isEditing && this.editingVacationId
      ? this.customVacationService.updateVacation(this.editingVacationId, payload)
      : this.customVacationService.createVacation(payload);

    request$.subscribe({
      next: () => {
        this.saveInProgress = false;
        this.resetForm();
        this.loadVacations(this.pagination.current_page);
      },
      error: (error) => {
        this.saveInProgress = false;
        this.formError = this.extractErrorMessage(error);
      }
    });
  }

  editVacation(vacation: CustomVacation): void {
    this.isEditing = true;
    this.editingVacationId = vacation.id;

    const departmentIds = vacation.departments?.map(dept => Number(dept.id)) ?? [];
    const subDepartmentIds = vacation.sub_departments?.map(sub => Number(sub.id)) ?? [];

    this.vacationForm.patchValue({
      name: vacation.name,
      start_date: vacation.start_date,
      end_date: vacation.end_date,
      is_full_day: vacation.is_full_day,
      description: vacation.description ?? '',
      department_ids: departmentIds,
      sub_department_ids: subDepartmentIds,
    }, { emitEvent: false });

    this.loadSubDepartmentsForSelectedDepartments(departmentIds, subDepartmentIds);
  }

  deleteVacation(vacation: CustomVacation): void {
    const confirmed = confirm(`Are you sure you want to remove "${vacation.name}"?`);
    if (!confirmed) {
      return;
    }

    this.customVacationService.deleteVacation(vacation.id).subscribe({
      next: () => {
        const shouldGoBack = this.vacations.length === 1 && this.pagination.current_page > 1;
        const targetPage = shouldGoBack ? this.pagination.current_page - 1 : this.pagination.current_page;
        this.loadVacations(targetPage);
      },
      error: () => {
        this.listError = 'Failed to delete the selected vacation.';
      }
    });
  }

  changePage(direction: 'prev' | 'next'): void {
    if (direction === 'prev' && this.pagination.current_page > 1) {
      this.loadVacations(this.pagination.current_page - 1);
    } else if (direction === 'next' && this.pagination.current_page < this.pagination.last_page) {
      this.loadVacations(this.pagination.current_page + 1);
    }
  }

  resetForm(): void {
    this.vacationForm.reset({
      name: '',
      start_date: '',
      end_date: '',
      is_full_day: true,
      description: '',
      department_ids: [],
      sub_department_ids: [],
    });
    this.isEditing = false;
    this.editingVacationId = null;
    this.formError = null;
    this.availableSubDepartments = [];
  }

  private buildPayload(): CustomVacationPayload {
    const formValue = this.vacationForm.value;
    return {
      name: formValue.name?.trim(),
      start_date: formValue.start_date,
      end_date: formValue.end_date,
      is_full_day: !!formValue.is_full_day,
      description: formValue.description ? formValue.description.trim() : null,
      department_ids: this.toNumberArray(formValue.department_ids ?? []),
      sub_department_ids: this.toNumberArray(formValue.sub_department_ids ?? []),
    };
  }

  private toNumberArray(values: any[]): number[] {
    return Array.isArray(values) ? values.map(value => Number(value)).filter(value => !isNaN(value)) : [];
  }

  private arraysEqual(a: number[], b: number[]): boolean {
    if (a.length !== b.length) {
      return false;
    }

    const sortedA = [...a].sort((x, y) => x - y);
    const sortedB = [...b].sort((x, y) => x - y);
    return sortedA.every((value, index) => value === sortedB[index]);
  }

  private extractErrorMessage(error: any): string {
    if (error?.error?.message) {
      return error.error.message;
    }

    if (typeof error?.error === 'string') {
      return error.error;
    }

    return 'An unexpected error occurred. Please try again.';
  }
}
