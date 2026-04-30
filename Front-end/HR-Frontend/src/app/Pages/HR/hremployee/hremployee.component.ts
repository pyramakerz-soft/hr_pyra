import { Component, HostListener } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ImportEmployeeDataPopUpComponent } from '../../../Components/import-employee-data-pop-up/import-employee-data-pop-up.component';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';
import Swal from 'sweetalert2';
import { ClockService } from '../../../Services/clock.service';
import { EmployeeHrProfileDialogComponent } from '../../../Components/employee-hr-profile-dialog/employee-hr-profile-dialog.component';
import { HrStateService } from '../../../Services/SaveState/hr-state.service';
import { BulkUpdateTimePopUpComponent } from '../../../Components/bulk-update-time-pop-up/bulk-update-time-pop-up.component';
import { B2bSlotService } from '../../../Services/b2b-slot.service';

interface data {
  Name: string,
  Code: string,
  Department: string,
  position: string,
  phone: string,
  Email: string,
  UserName: string,
}

@Component({
  selector: 'app-hremployee',
  standalone: true,
  imports: [CommonModule, FormsModule, SideBarComponent],
  templateUrl: './hremployee.component.html',
  styleUrl: './hremployee.component.css'
})
export class HREmployeeComponent {

  constructor(public dialog: MatDialog, private router: Router, public userServ: UserServiceService , private clockService: ClockService, private hrStateService: HrStateService, private b2bSlotService: B2bSlotService) { }

  tableData: UserModel[] = [];
  isMenuOpen: boolean = false;
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot: boolean = true;
  UsersNames: string[] = [];
  filteredUsers: string[] = [];
  isLoading: boolean = false; // Add isLoading state
  
  activeDropdownId: number | null = null;
  isVacationModalOpen: boolean = false;
  selectedEmployeeForVacation: number | null = null;
  vacationTypes: any[] = [];
  vacationData = {
    vacation_type_id: null,
    from_date: '',
    to_date: '',
    is_half_day: false
  };

  // B2B Fixed Excuse logic
  showFixedExcusePopup = false;
  selectedB2BEmployee: any = null;
  activeSlotForEmployee: any = null;
  slotFormLoading = false;
  slotForm = {
    day_of_week: 'sunday',
    position: 'start',
    slot_from: '09:00',
    expires_at: ''
  };

  isB2BEmployee(row: any): boolean {
    return (row.department ?? '').toUpperCase().includes('B2B');
  }

  openFixedExcusePopup(row: any) {
    this.selectedB2BEmployee = row;
    this.activeSlotForEmployee = null;
    this.showFixedExcusePopup = true;
    this.activeDropdownId = null;
    
    this.b2bSlotService.getActiveSlotForUser(row.id).subscribe({
      next: (res: any) => {
        this.activeSlotForEmployee = res.slot ?? null;
        if (this.activeSlotForEmployee) {
          this.slotForm.day_of_week = this.activeSlotForEmployee.day_of_week;
          this.slotForm.position = this.activeSlotForEmployee.position;
          this.slotForm.slot_from = this.activeSlotForEmployee.slot_from.substring(0, 5);
          this.slotForm.expires_at = this.activeSlotForEmployee.expires_at ? this.activeSlotForEmployee.expires_at.split('T')[0] : '';
        } else {
          this.slotForm = {
            day_of_week: 'sunday',
            position: 'start',
            slot_from: '09:00',
            expires_at: ''
          };
        }
      },
      error: () => {
        this.activeSlotForEmployee = null;
      }
    });
  }

  closeFixedExcusePopup() {
    this.showFixedExcusePopup = false;
    this.selectedB2BEmployee = null;
    this.activeSlotForEmployee = null;
    this.slotForm = {
      day_of_week: 'sunday',
      position: 'start',
      slot_from: '09:00',
      expires_at: ''
    };
  }

  saveFixedExcuse() {
    if (!this.selectedB2BEmployee) return;
    this.slotFormLoading = true;
    const payload = {
      user_id: this.selectedB2BEmployee.id,
      ...this.slotForm
    };
    this.b2bSlotService.createSlot(payload).subscribe({
      next: () => {
        this.slotFormLoading = false;
        this.closeFixedExcusePopup();
        Swal.fire({
          title: 'Saved',
          text: 'Fixed excuse slot assigned successfully.',
          icon: 'success',
          confirmButtonColor: '#FF7519'
        });
      },
      error: (err: any) => {
        this.slotFormLoading = false;
        Swal.fire({
          title: 'Error',
          text: err.error?.message || 'Failed to save slot.',
          icon: 'error',
          confirmButtonColor: '#FF7519'
        });
      }
    });
  }

  deactivateFixedExcuse() {
    if (!this.activeSlotForEmployee) return;
    
    Swal.fire({
      title: 'Are you sure?',
      text: "You want to deactivate this fixed excuse slot?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Yes, deactivate it!'
    }).then((result) => {
      if (result.isConfirmed) {
        this.slotFormLoading = true;
        this.b2bSlotService.deactivateSlot(this.activeSlotForEmployee.id).subscribe({
          next: () => {
            this.slotFormLoading = false;
            this.closeFixedExcusePopup();
            Swal.fire({
              title: 'Deactivated!',
              text: 'The fixed excuse slot has been deactivated.',
              icon: 'success',
              confirmButtonColor: '#FF7519'
            });
          },
          error: (err: any) => {
            this.slotFormLoading = false;
            Swal.fire({
              title: 'Error',
              text: err.error?.message || 'Failed to deactivate slot.',
              icon: 'error',
              confirmButtonColor: '#FF7519'
            });
          }
        });
      }
    });
  }

  isNavigateingToImportPopUp = false

ngOnInit() {
  // Get saved state
  const savedState = this.hrStateService.getEmployeeState();
  
  if (savedState.tableData && savedState.tableData.length > 0) {
    this.selectedName = savedState.searchQuery;
    this.CurrentPageNumber = savedState.currentPage;
    this.tableData = savedState.tableData;
    this.PagesNumber = savedState.pagesNumber;
    // this.DisplayPagginationOrNot = savedState.displayPagination;
    
    // Clear the state so it doesn't persist on refresh
    this.hrStateService.clearEmployeeState();
  } else {
    // Original initialization
    const savedPageNumber = localStorage.getItem('HrEmployeeCN');
    if (savedPageNumber) {
      this.CurrentPageNumber = parseInt(savedPageNumber, 10);
    } else {
      this.CurrentPageNumber = 1;
    }
    this.getAllEmployees(this.CurrentPageNumber);
  }
  
  this.getUsersName();
  this.getVacationTypes();
  
  localStorage.setItem('HrLocationsCN', "1");
  localStorage.setItem('HrAttendaceCN', "1");
  localStorage.setItem('HrAttanceDetailsCN', "1");
}

@HostListener('document:click', ['$event'])
onDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('.action-dropdown-container')) {
    this.activeDropdownId = null;
  }
}

getVacationTypes() {
  this.userServ.getVacationTypes().subscribe(
    (res: any) => {
      this.vacationTypes = res.data;
    },
    (err) => console.error('Error fetching vacation types', err)
  );
}

toggleDropdown(id: number, event: Event) {
  event.stopPropagation();
  if (this.activeDropdownId === id) {
    this.activeDropdownId = null;
  } else {
    this.activeDropdownId = id;
  }
}

openVacationModal(id: number) {
  this.selectedEmployeeForVacation = id;
  this.isVacationModalOpen = true;
  this.activeDropdownId = null; // close dropdown
  this.vacationData = {
    vacation_type_id: null,
    from_date: '',
    to_date: '',
    is_half_day: false
  };
}

closeVacationModal() {
  this.isVacationModalOpen = false;
  this.selectedEmployeeForVacation = null;
}

submitVacation() {
  if (!this.selectedEmployeeForVacation || !this.vacationData.from_date || !this.vacationData.to_date) {
    Swal.fire('Error', 'Please fill all required fields', 'error');
    return;
  }

  const payload = {
    user_id: this.selectedEmployeeForVacation,
    ...this.vacationData
  };

  this.isLoading = true;
  this.userServ.addUserVacation(payload).subscribe(
    (res: any) => {
      this.isLoading = false;
      this.closeVacationModal();
      Swal.fire('Success', 'Vacation successfully added and approved', 'success');
    },
    (err: any) => {
      this.isLoading = false;
      Swal.fire('Error', err.error?.message || 'Failed to add vacation', 'error');
    }
  );
}

   downloadExcelTemplate() {
    this.isLoading = true; // Show spinner
    this.clockService.downloadAllUsersExcel().subscribe(
      (blob: Blob) => {
        this.isLoading = false; // Hide spinner
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'all_users.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      },
      (error) => {
        this.isLoading = false; // Hide spinner on error
        if (error.status === 404) {
          Swal.fire({
            text: "No user records found.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        } else {
          Swal.fire({
            text: "An error occurred while downloading the Excel file. Please try again.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        }
      }
    );
  }

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  OpenImportPopUp() {
    this.isNavigateingToImportPopUp = true
    const dialogRef = this.dialog.open(ImportEmployeeDataPopUpComponent, {
    });
    dialogRef.afterClosed().subscribe(() => {
      this.isNavigateingToImportPopUp = false;
    });

  }

  NavigateToAddEmployee() {
    this.router.navigateByUrl("HR/HREmployeeDetailsAdd")
  }

  OpenBulkUpdateTimePopUp() {
    const dialogRef = this.dialog.open(BulkUpdateTimePopUpComponent, {
      width: '500px',
      maxWidth: '95vw'
    });
    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        this.getAllEmployees(this.CurrentPageNumber);
      }
    });
  }

NavigateToEmployeeDetails(id: number) {
  this.hrStateService.saveEmployeeState({
    searchQuery: this.selectedName,
    currentPage: this.CurrentPageNumber,
    tableData: this.tableData,
    pagesNumber: this.PagesNumber,
    displayPagination: this.DisplayPagginationOrNot
  });
  
  this.router.navigateByUrl(`HR/HREmployeeDetails/${id}`);
}

  NavigateToEditEmployee(empId: number) {
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }

  getAllEmployees(PgNumber: number) {
    this.CurrentPageNumber = PgNumber;
    this.saveCurrentPageNumber();

    this.userServ.getall(PgNumber).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber = d.data.pagination.last_page;
        this.generatePages();
      }
    );
  }

  generatePages() {
    this.pages = [];
    for (let i = 1; i <= this.PagesNumber; i++) {
      this.pages.push(i);
    }
  }

  getNextPage() {
    this.CurrentPageNumber++;
    this.saveCurrentPageNumber();
    this.getAllEmployees(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.saveCurrentPageNumber();
    this.getAllEmployees(this.CurrentPageNumber);
  }

  saveCurrentPageNumber() {
    localStorage.setItem('HrEmployeeCN', this.CurrentPageNumber.toString());
  }


  Search() {
    if (this.selectedName) {
      this.userServ.SearchByNameAndDeptAndSubDep(this.selectedName).subscribe(
        (d: any) => {
          this.tableData = d.data.users;
          this.PagesNumber = 1;
          this.DisplayPagginationOrNot = false;
          this.filteredUsers = [];
        }
      );
    }
    else {
      this.DisplayPagginationOrNot = true;
    }
  }


  getUsersName() {
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        const list = d?.usersNames ?? [];
        this.UsersNames = (Array.isArray(list) ? list : [])
          .map((item: any) => String(item?.name ?? item ?? ''))
          .filter((name) => name.trim() !== '');
      }
    );
  }


  filterByName() {
    // this.getLocationsName();
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      // If the input is empty, call getAllLocations with the current page number
      this.getAllEmployees(this.CurrentPageNumber);
      this.DisplayPagginationOrNot = true;
      this.filteredUsers = []; // Clear the dropdown list
    } else {
      this.filteredUsers = this.UsersNames;
      this.filteredUsers = this.UsersNames.filter(name =>
        name.toLowerCase().includes(query)
      );
    }
  }

  selectUser(location: string) {
    this.selectedName = location;
    this.userServ.SearchByNameAndDeptAndSubDep(this.selectedName).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.DisplayPagginationOrNot = false;
      },
    );

  }

  resetfilteredUsers() {
    this.filteredUsers = [];

  }


  DeleteEmp(id: number) {

    Swal.fire({
      title: 'Are you sure you want to Delete This Employee?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.userServ.DeleteById(id).subscribe(
          (d: any) => {
            if(this.tableData.length==1&&this.CurrentPageNumber-1>=1){
              this.getAllEmployees(this.CurrentPageNumber-1);
            }
            else{
              this.getAllEmployees(this.CurrentPageNumber);
            }
            this.getUsersName()
          }
        );

      }
    });
  }

  openHrProfileDialog(userId: number) {
    this.dialog.open(EmployeeHrProfileDialogComponent, {
      data: { userId },
      width: '900px',
      maxWidth: '95vw',
      panelClass: 'employee-hr-profile-dialog-panel'
    });
  }
}

