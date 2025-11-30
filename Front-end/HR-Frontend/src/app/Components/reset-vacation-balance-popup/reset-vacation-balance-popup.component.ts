import { Component, Inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { UserServiceService } from '../../Services/user-service.service';
import { DepartmentService } from '../../Services/department.service';
import { SubDepartmentService } from '../../Services/sub-department.service';
import Swal from 'sweetalert2';

@Component({
    selector: 'app-reset-vacation-balance-popup',
    standalone: true,
    imports: [CommonModule, ReactiveFormsModule, MatDialogModule],
    templateUrl: './reset-vacation-balance-popup.component.html',
    styleUrls: ['./reset-vacation-balance-popup.component.css']
})
export class ResetVacationBalancePopupComponent implements OnInit {
    resetForm: FormGroup;
    departments: any[] = [];
    subDepartments: any[] = [];
    users: any[] = []; // List of users for dropdown
    filteredUsers: any[] = []; // Filtered list for search
    isSubmitting = false;
    errorMessage: string | null = null;
    selectedUserName: string = '';

    constructor(
        private fb: FormBuilder,
        private userService: UserServiceService,
        private departmentService: DepartmentService,
        private subDepartmentService: SubDepartmentService,
        public dialogRef: MatDialogRef<ResetVacationBalancePopupComponent>,
        @Inject(MAT_DIALOG_DATA) public data: { userId?: number, userName?: string }
    ) {
        this.resetForm = this.fb.group({
            year: [new Date().getFullYear(), [Validators.required]],
            target_type: ['user', [Validators.required]],
            user_id: [data?.userId || null],
            department_id: [null],
            sub_department_id: [null]
        });

        if (data?.userName) {
            this.selectedUserName = data.userName;
        }
    }

    ngOnInit(): void {
        this.loadDepartments();
        if (!this.data?.userId) {
            this.loadUsers();
        }

        // Reset validators based on target type
        this.resetForm.get('target_type')?.valueChanges.subscribe(type => {
            this.errorMessage = null;
            if (type === 'department') {
                this.resetForm.get('department_id')?.setValidators([Validators.required]);
                this.resetForm.get('sub_department_id')?.clearValidators();
                this.resetForm.get('user_id')?.clearValidators();
            } else if (type === 'sub_department') {
                this.resetForm.get('sub_department_id')?.setValidators([Validators.required]);
                this.resetForm.get('department_id')?.clearValidators();
                this.resetForm.get('user_id')?.clearValidators();
            } else { // user
                this.resetForm.get('department_id')?.clearValidators();
                this.resetForm.get('sub_department_id')?.clearValidators();
                this.resetForm.get('user_id')?.setValidators([Validators.required]);
            }
            this.resetForm.get('department_id')?.updateValueAndValidity();
            this.resetForm.get('sub_department_id')?.updateValueAndValidity();
            this.resetForm.get('user_id')?.updateValueAndValidity();
        });
    }

    loadUsers() {
        this.userService.getAllUsersName().subscribe((res: any) => {
            this.users = res.usersNames || [];
            this.filteredUsers = this.users;
        });
    }

    filterUsers(event: any) {
        const query = event.target.value.toLowerCase();
        this.filteredUsers = this.users.filter(u => u.name.toLowerCase().includes(query));
    }

    selectUser(user: any) {
        this.resetForm.patchValue({ user_id: user.id });
        this.selectedUserName = user.name;
    }

    loadDepartments() {
        this.departmentService.getall().subscribe({
            next: (res: any) => {
                console.log('Department API Response:', res);
                this.departments = res.data?.departments || res.data || res || [];
                console.log('Loaded departments:', this.departments);
            },
            error: (err) => {
                console.error('Error loading departments:', err);
                this.errorMessage = 'Failed to load departments';
            }
        });
    }

    onDepartmentChange(event: any) {
        const deptId = event.target.value;
        console.log('Selected department ID:', deptId);

        if (deptId) {
            this.subDepartmentService.getall(deptId).subscribe({
                next: (res: any) => {
                    console.log('Sub-Department API Response:', res);
                    this.subDepartments = res.data || res || [];
                    console.log('Loaded sub-departments:', this.subDepartments);
                },
                error: (err) => {
                    console.error('Error loading sub-departments:', err);
                    this.errorMessage = 'Failed to load sub-departments';
                    this.subDepartments = [];
                }
            });
        } else {
            this.subDepartments = [];
        }
    }

    submitForm() {
        if (this.resetForm.invalid) {
            this.errorMessage = 'Please fill in all required fields.';
            return;
        }

        this.isSubmitting = true;
        this.errorMessage = null;

        const formValue = this.resetForm.value;
        let payload: any = { year: formValue.year };

        if (formValue.target_type === 'user') {
            payload.user_id = formValue.user_id;
        } else if (formValue.target_type === 'department') {
            payload.department_id = formValue.department_id;
        } else if (formValue.target_type === 'sub_department') {
            payload.sub_department_id = formValue.sub_department_id;
        }

        this.userService.resetVacationBalance(payload).subscribe({
            next: (res) => {
                this.isSubmitting = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message || 'Vacation balance reset successfully.',
                    confirmButtonColor: '#FF7519'
                });
                this.dialogRef.close(true);
            },
            error: (err) => {
                this.isSubmitting = false;
                this.errorMessage = err.error?.message || 'An error occurred.';
            }
        });
    }
}
