import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import Swal from 'sweetalert2';
import { DepartmentService } from '../../Services/department.service';
import { SubDepartmentService } from '../../Services/sub-department.service';
import { UserServiceService } from '../../Services/user-service.service';
import { Department } from '../../Models/department';
import { SubDepartment } from '../../Models/sub-department';

@Component({
  selector: 'app-bulk-update-time-pop-up',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './bulk-update-time-pop-up.component.html',
  styleUrl: './bulk-update-time-pop-up.component.css'
})
export class BulkUpdateTimePopUpComponent implements OnInit {
  departments: Department[] = [];
  subDepartments: SubDepartment[] = [];
  
  selectedDepartmentId: number | null = null;
  selectedSubDepartmentId: number | null = null;
  startTime: string = "";
  endTime: string = "";
  workingHoursPerDay: number | null = null;
  
  isSubmitting: boolean = false;

  constructor(
    public dialogRef: MatDialogRef<BulkUpdateTimePopUpComponent>,
    private departmentService: DepartmentService,
    private subDepartmentService: SubDepartmentService,
    private userService: UserServiceService
  ) {}

  ngOnInit(): void {
    this.loadDepartments();
  }

  loadDepartments(): void {
    this.departmentService.getall().subscribe({
      next: (res: any) => {
        this.departments = res.data.departments;
      },
      error: (err) => {
        console.error('Error loading departments', err);
      }
    });
  }

  onDepartmentChange(): void {
    this.selectedSubDepartmentId = null;
    if (this.selectedDepartmentId) {
      this.subDepartmentService.getall(this.selectedDepartmentId).subscribe({
        next: (res: any) => {
          this.subDepartments = res.data;
        },
        error: (err) => {
          console.error('Error loading sub-departments', err);
        }
      });
    } else {
      this.subDepartments = [];
    }
  }

  closeDialog(): void {
    this.dialogRef.close();
  }

  updateTimes(): void {
    if (!this.selectedDepartmentId && !this.selectedSubDepartmentId) {
       Swal.fire({
          icon: 'error',
          title: 'Required Field',
          text: 'Please select a Department.',
          confirmButtonColor: '#FF7519'
       });
       return;
    }

    if (!this.startTime && !this.endTime && !this.workingHoursPerDay) {
       Swal.fire({
          icon: 'error',
          title: 'Required Field',
          text: 'Please provide at least one of Start Time, End Time or Working Hours Per Day.',
          confirmButtonColor: '#FF7519'
       });
       return;
    }

    this.isSubmitting = true;
    const payload = {
      department_id: this.selectedDepartmentId ? Number(this.selectedDepartmentId) : undefined,
      sub_department_id: this.selectedSubDepartmentId ? Number(this.selectedSubDepartmentId) : undefined,
      start_time: this.startTime || undefined,
      end_time: this.endTime || undefined,
      working_hours_day: this.workingHoursPerDay || undefined
    };

    this.userService.bulkUpdateTime(payload).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: 'Employee times updated successfully',
          confirmButtonColor: '#FF7519'
        }).then(() => {
          this.dialogRef.close(true);
        });
      },
      error: (err) => {
        this.isSubmitting = false;
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: err.error?.message || 'An error occurred while updating times.',
          confirmButtonColor: '#FF7519'
        });
      }
    });
  }
}
