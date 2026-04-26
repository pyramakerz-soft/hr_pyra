import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SalaryAdjustmentService } from '../../../Services/salary-adjustment.service';
import { UserServiceService } from '../../../Services/user-service.service';
import { SalaryAdjustment } from '../../../Models/salary-adjustment';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-adjustments',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-adjustments.component.html',
  styleUrl: './hr-adjustments.component.css'
})
export class HrAdjustmentsComponent implements OnInit {
  adjustments: SalaryAdjustment[] = [];
  users: any[] = [];
  isLoading = false;
  isSaving = false;
  
  // Pagination
  currentPage = 1;
  totalPages = 1;
  perPage = 10;

  // Filters
  fromDate: string = '';
  toDate: string = '';

  // Modal State
  isModalOpen = false;
  isEditMode = false;
  currentAdjustment: SalaryAdjustment = this.resetAdjustment();

  constructor(
    private adjustmentService: SalaryAdjustmentService,
    private userService: UserServiceService
  ) {}

  ngOnInit(): void {
    this.setDefaultDateRange();
    this.loadAdjustments();
    this.loadUsers();
  }

  setDefaultDateRange(): void {
    const today = new Date();
    let start, end;

    if (today.getDate() >= 26) {
      start = new Date(today.getFullYear(), today.getMonth(), 26);
      end = new Date(today.getFullYear(), today.getMonth() + 1, 25);
    } else {
      start = new Date(today.getFullYear(), today.getMonth() - 1, 26);
      end = new Date(today.getFullYear(), today.getMonth(), 25);
    }

    this.fromDate = start.toISOString().split('T')[0];
    this.toDate = end.toISOString().split('T')[0];
  }

  loadAdjustments(page: number = 1): void {
    this.isLoading = true;
    this.adjustmentService.getAdjustments(page, this.fromDate, this.toDate).subscribe({
      next: (res: any) => {
        if (res && res.adjustments) {
          this.adjustments = res.adjustments.data;
          this.currentPage = res.adjustments.current_page;
          this.totalPages = res.adjustments.last_page;
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading adjustments', err);
        this.isLoading = false;
      }
    });
  }

  loadUsers(): void {
    this.userService.getAllUsersName().subscribe({
      next: (res: any) => {
        this.users = res.usersNames || [];
      }
    });
  }

  resetAdjustment(): SalaryAdjustment {
    return {
      user_id: 0,
      amount: 0,
      reason: '',
      adjustment_date: new Date().toISOString().split('T')[0]
    };
  }

  openAddModal(): void {
    this.isEditMode = false;
    this.currentAdjustment = this.resetAdjustment();
    this.isModalOpen = true;
  }

  openEditModal(adj: SalaryAdjustment): void {
    this.isEditMode = true;
    this.currentAdjustment = { ...adj };
    this.isModalOpen = true;
  }

  closeModal(): void {
    this.isModalOpen = false;
  }

  saveAdjustment(): void {
    if (!this.currentAdjustment.user_id || !this.currentAdjustment.amount || !this.currentAdjustment.reason) {
      Swal.fire('Error', 'Please fill all required fields', 'error');
      return;
    }

    this.isSaving = true;
    const observer = {
      next: () => {
        this.isSaving = false;
        this.closeModal();
        this.loadAdjustments(this.currentPage);
        Swal.fire('Success', `Adjustment ${this.isEditMode ? 'updated' : 'added'} successfully`, 'success');
      },
      error: (err: any) => {
        this.isSaving = false;
        Swal.fire('Error', err.error?.message || 'Something went wrong', 'error');
      }
    };

    if (this.isEditMode && this.currentAdjustment.id) {
      this.adjustmentService.updateAdjustment(this.currentAdjustment.id, this.currentAdjustment).subscribe(observer);
    } else {
      this.adjustmentService.createAdjustment(this.currentAdjustment).subscribe(observer);
    }
  }

  deleteAdjustment(id: number): void {
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        this.adjustmentService.deleteAdjustment(id).subscribe({
          next: () => {
            this.loadAdjustments(this.currentPage);
            Swal.fire('Deleted!', 'Adjustment has been deleted.', 'success');
          }
        });
      }
    });
  }

  exportData(): void {
    this.isLoading = true;
    this.adjustmentService.exportAdjustments(this.fromDate, this.toDate).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Salary_Adjustments_${this.fromDate}_to_${this.toDate}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Export failed', err);
        this.isLoading = false;
        Swal.fire('Error', 'Export failed. It might be that the export endpoint is not yet implemented on backend.', 'error');
      }
    });
  }

  onFilterChange(): void {
    this.loadAdjustments(1);
  }

  getPrevPage(): void {
    if (this.currentPage > 1) this.loadAdjustments(this.currentPage - 1);
  }

  getNextPage(): void {
    if (this.currentPage < this.totalPages) this.loadAdjustments(this.currentPage + 1);
  }
}
