import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, ParamMap, Router } from '@angular/router';
import { Department } from '../../../Models/department';
import { UserModel } from '../../../Models/user-model';
import { ClockService } from '../../../Services/clock.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { UserServiceService } from '../../../Services/user-service.service';
import Swal from 'sweetalert2';
import { ResetVacationBalancePopupComponent } from '../../../Components/reset-vacation-balance-popup/reset-vacation-balance-popup.component';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { HrStateService } from '../../../Services/SaveState/hr-state.service';

@Component({
  selector: 'app-hr-attendance',
  standalone: true,
  imports: [CommonModule, FormsModule, MatDialogModule],
  templateUrl: './hr-attendance.component.html',
  styleUrl: './hr-attendance.component.css'
})
export class HrAttendanceComponent {
  @ViewChild('balanceFileInput') balanceFileInput!: ElementRef;

  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot: boolean = true;
  UsersNames: string[] = [];
  filteredUsers: string[] = [];
  loading: boolean = false;
  errorMessage: string = '';
  isLoading: boolean = false;
  from_day: string = '';
  to_day: string = '';
  selectedUsers: { userId: number, userName: string }[] = [];
  selectedMonth: string = "01";
  selectedYear: number = 0;
  SelectDepartment: string = "AllDepartment";
  departments: Department[] = [];
  DateString: string = "2019-01";
  isSelectAllChecked: boolean = false;
  months = [
    { name: 'January', value: "01" },
    { name: 'February', value: "02" },
    { name: 'March', value: "03" },
    { name: 'April', value: "04" },
    { name: 'May', value: "05" },
    { name: 'June', value: "06" },
    { name: 'July', value: "07" },
    { name: 'August', value: "08" },
    { name: 'September', value: "09" },
    { name: 'October', value: "10" },
    { name: 'November', value: "11" },
    { name: 'December', value: "12" }
  ];
  years: number[] = [];
  subDepartments: any[] = [];
  selectedDepartment: number | 'all' | 'none' | null = null;
  selectedSubDepartmentIds: number[] = [];
  pendingSubDepartmentIds: number[] = [];
  isSubDepartmentDropdownOpen = false;

  tableData: UserModel[] = [];
  readonly allDepartmentsValue = 'all';
  readonly noDepartmentValue = 'none';

  constructor(
    public router: Router,
    private route: ActivatedRoute,
    public userServ: UserServiceService,
    public UserClocksService: ClockService,
    private clockService: ClockService,
    public departmentServ: DepartmentService,
    public supDeptServ: SubDepartmentService,
    public dialog: MatDialog,
    private hrStateService: HrStateService

  ) { }

  ngOnInit() {
    const savedState = this.hrStateService.getAttendanceState();

    if (savedState.tableData && savedState.tableData.length > 0) {
      this.selectedName = savedState.searchQuery;
      this.CurrentPageNumber = savedState.currentPage;
      this.tableData = savedState.tableData;
      this.PagesNumber = savedState.pagesNumber;
      this.from_day = savedState.from_day;
      this.to_day = savedState.to_day;
      this.selectedDepartment = savedState.selectedDepartment;
      this.selectedSubDepartmentIds = savedState.selectedSubDepartmentIds;
      this.DisplayPagginationOrNot = savedState.displayPagination;

      this.hrStateService.clearAttendanceState();

      if (typeof this.selectedDepartment === 'number') {
        this.supDeptServ.setDeptId(this.selectedDepartment);
        this.getSubDepartments(this.selectedDepartment, false);
      }
    } else {
      const savedPageNumber = localStorage.getItem('HrAttendaceCN');
      if (savedPageNumber) {
        this.CurrentPageNumber = parseInt(savedPageNumber, 10);
      } else {
        this.CurrentPageNumber = 1;
      }

      this.route.queryParamMap.subscribe((params) => {
        this.applyQueryParams(params);
        this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
      });
    }

    this.getUsersName();
    this.GetAllDepartment();
    this.populateYears();
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1;
    this.selectedMonth = currentMonth < 10 ? `0${currentMonth}` : `${currentMonth}`;
    this.selectedYear = currentDate.getFullYear();
    this.SelectDepartment = "AllDepartment";
    this.DateString = this.selectedYear + "-" + this.selectedMonth;
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");
  }

  openResetVacationBalanceDialog() {
    this.dialog.open(ResetVacationBalancePopupComponent, {
      data: { userId: null, userName: null },
      width: '500px',
      maxHeight: '90vh',
      autoFocus: true,
      restoreFocus: true,
      disableClose: false,
      panelClass: 'custom-dialog-container',
      hasBackdrop: true,
      backdropClass: 'custom-backdrop'
    });
  }

  // Method to handle "Select All" checkbox state change
  toggleSelectAll() {
    this.selectedUsers = [];
    if (!this.isSelectAllChecked) {
      this.selectedUsers = [];
      this.isSelectAllChecked = false;
    } else {
      this.tableData.forEach(row => {
        this.selectedUsers.push({ userId: row.id, userName: row.name });
        this.isSelectAllChecked = true;
      });
    }
  }

  isUserSelected(userId: number): boolean {
    return this.selectedUsers.some(u => u.userId === userId);
  }

  onUserSelectionChange(row: any): void {
    if (row.selected) {
      this.selectedUsers.push({ userId: row.id, userName: row.name });
    } else {
      this.selectedUsers = this.selectedUsers.filter(u => u.userId !== row.id);
    }
  }

  ExportData() {
    if (this.selectedUsers.length === 0) return;
    this.isLoading = true;
    const ids = this.selectedUsers.map(u => u.userId);

    this.clockService.exportSelectedUsers(ids, this.from_day, this.to_day)
      .subscribe((result: Blob) => {
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `all_user_clocks_${this.from_day || 'all'}_${this.to_day || 'all'}.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      });
  }

  ExportAbsentUserData() {
    this.isLoading = true;

    // Logic similar to ExportData (Clocks)
    // Gather details
    let ids: number[] = [];
    if (this.selectedUsers.length > 0) {
      ids = this.selectedUsers.map(u => u.userId);
    }

    const departmentFilter = this.getDepartmentFilterValue();

    // We pass filters to the service.
    // If specific users are selected (ids), those take precedence in the backend usually, checking the implementation.
    // Start/End date are this.from_day, this.to_day.

    // Note: The service signature is (fromDate, toDate, departmentId, userId, ids)
    // We don't have a specific single userId selected unless we consider filtering by name?
    // In search/filterByName, we filter locally or via API but don't hold a single ID unless needed.
    // passing null for userId.

    this.clockService.ExportAbsentUserData(
      this.from_day,
      this.to_day,
      departmentFilter,
      null,
      ids
    )
      .subscribe((result: Blob) => {
        const url = window.URL.createObjectURL(result);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `absent_users_${this.from_day || 'all'}_${this.to_day || 'all'}.xlsx`;
        anchor.click();
        window.URL.revokeObjectURL(url);
        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      },
      );
  }

  ExportLeaveHistory() {
    this.isLoading = true;
    let ids: number[] = [];
    if (this.selectedUsers.length > 0) {
      ids = this.selectedUsers.map(u => u.userId);
    }
    const departmentFilter = this.getDepartmentFilterValue();

    this.userServ.exportLeaveHistory(
      this.from_day,
      this.to_day,
      departmentFilter,
      null,
      ids
    ).subscribe((result: Blob) => {
      const url = window.URL.createObjectURL(result);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `leaves_history_${this.from_day || 'all'}_${this.to_day || 'all'}.xlsx`;
      anchor.click();
      window.URL.revokeObjectURL(url);
      this.isLoading = false;
    }, () => {
      this.isLoading = false;
    });
  }

  NavigateToEmployeeAttendanceDetails(EmpId: number) {
    this.hrStateService.saveAttendanceState({
      searchQuery: this.selectedName,
      currentPage: this.CurrentPageNumber,
      tableData: this.tableData,
      pagesNumber: this.PagesNumber,
      from_day: this.from_day,
      to_day: this.to_day,
      selectedDepartment: this.selectedDepartment,
      selectedSubDepartmentIds: this.selectedSubDepartmentIds,
      displayPagination: this.DisplayPagginationOrNot
    });

    this.router.navigateByUrl("HR/HRAttendanceEmployeeDetails/" + EmpId);
  }

  ImportOpeningBalanceSheet() {
    this.balanceFileInput.nativeElement.click();
  }

  onBalanceFileSelected(event: any): void {
    const file: File = event.target.files[0];
    if (file) {
      this.importVacationBalances(file);
    }
  }

  importVacationBalances(file: File): void {
    this.isLoading = true;
    this.userServ.importVacationBalances(file).subscribe({
      next: (response) => {
        Swal.fire({
          icon: 'success',
          title: 'Import Successful',
          text: response.message || 'Vacation balances imported successfully!',
          confirmButtonColor: '#17253E',
        });
      },
      error: (error) => {
        let errorMessage = 'An error occurred during import.';
        if (error.error && typeof error.error.message === 'string') {
          errorMessage = error.error.message;
        }
        Swal.fire({
          icon: 'error',
          title: 'Import Failed',
          text: errorMessage,
          confirmButtonColor: '#FF7519',
        });
        this.isLoading = false;
      },
      complete: () => {
        this.isLoading = false;
        if (this.balanceFileInput) {
          this.balanceFileInput.nativeElement.value = '';
        }
      }
    });
  }

  private applyQueryParams(params: ParamMap): void {
    const departmentIdParam = params.get('departmentId');
    const noDepartmentParam = params.get('noDepartment');
    const allDepartmentsParam = params.get('allDepartments');
    const subDepartmentParam = params.get('sub_department_ids');

    const parsedSubIds = this.parseIdList(subDepartmentParam);

    if (departmentIdParam) {
      const parsedId = Number(departmentIdParam);
      if (!Number.isNaN(parsedId)) {
        this.selectedDepartment = parsedId;
        this.selectedSubDepartmentIds = [];
        this.pendingSubDepartmentIds = parsedSubIds;
        this.subDepartments = [];
        this.supDeptServ.setDeptId(parsedId);
        this.getSubDepartments(parsedId, true);
        this.CurrentPageNumber = 1;
        return;
      }
    }

    if (noDepartmentParam === '1') {
      this.selectedDepartment = this.noDepartmentValue;
      this.selectedSubDepartmentIds = [];
      this.pendingSubDepartmentIds = [];
      this.subDepartments = [];
      this.CurrentPageNumber = 1;
      return;
    }

    if (allDepartmentsParam === '1') {
      this.selectedDepartment = this.allDepartmentsValue;
      this.selectedSubDepartmentIds = [];
      this.pendingSubDepartmentIds = [];
      this.subDepartments = [];
      this.CurrentPageNumber = 1;
      return;
    }

    this.pendingSubDepartmentIds = [];
  }

  getAllEmployees(pgNumber: number, from_day: string = '', to_day: string = '') {
    this.CurrentPageNumber = pgNumber;
    this.saveCurrentPageNumber();
    const departmentFilter = this.getDepartmentFilterValue();
    const options: { allDepartments?: boolean; departmentId?: number | 'none'; subDepartmentIds?: number[] } = {};

    if (this.isAllDepartmentsSelected()) {
      options.allDepartments = true;
    } else if (this.isNoDepartmentSelected()) {
      options.departmentId = this.noDepartmentValue;
    } else if (departmentFilter !== null) {
      options.departmentId = departmentFilter;
      if (this.selectedSubDepartmentIds.length > 0) {
        options.subDepartmentIds = [...this.selectedSubDepartmentIds];
      }
    }

    this.userServ.getall(pgNumber, from_day, to_day, options).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        const pagination = d.data.pagination;
        const lastPage = pagination?.last_page ?? 1;
        const hasPagination = !!pagination && !(options.allDepartments || (from_day && to_day));
        this.DisplayPagginationOrNot = hasPagination;
        this.PagesNumber = hasPagination ? lastPage : 1;
        this.generatePages();
      },
      () => { }
    );
  }

  GetAllDepartment() {
    this.departmentServ.getall().subscribe(
      (d: any) => {
        this.departments = d.data.departments;
      }
    );
  }

  onDepartmentChange() {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    this.subDepartments = [];
    this.selectedSubDepartmentIds = [];
    this.pendingSubDepartmentIds = [];
    this.isSubDepartmentDropdownOpen = false;
    this.CurrentPageNumber = 1;

    if (this.isAllDepartmentsSelected()) {
      this.loadAllDepartmentsUsers();
      return;
    }

    if (this.isNoDepartmentSelected()) {
      this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
      return;
    }

    if (typeof this.selectedDepartment === 'number') {
      this.supDeptServ.setDeptId(this.selectedDepartment);
      this.getSubDepartments(this.selectedDepartment, true);
    } else {
      this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
    }
  }

  getSubDepartments(departmentId: number, triggerFilter = false) {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    this.supDeptServ.getall(departmentId).subscribe(
      (res: any) => {
        this.subDepartments = res.data || res;
        const availableIds = this.subDepartments.map((sub: any) => Number(sub.id));

        if (this.pendingSubDepartmentIds.length > 0) {
          this.selectedSubDepartmentIds = this.pendingSubDepartmentIds.filter(id => availableIds.includes(id));
          this.pendingSubDepartmentIds = [];
        } else {
          this.selectedSubDepartmentIds = this.selectedSubDepartmentIds.filter(id => availableIds.includes(id));
        }

        if (triggerFilter || this.selectedSubDepartmentIds.length > 0) {
          this.applySubDepartmentFilter();
        }
      },
      (err) => {
        console.error('Failed to fetch sub-departments', err);
      }
    );
  }

  toggleSubDepartmentDropdown(event: Event) {
    event.stopPropagation();
    this.isSubDepartmentDropdownOpen = !this.isSubDepartmentDropdownOpen;
  }

  onSubDepartmentToggle(id: number, checked: boolean) {
    const numericId = Number(id);
    if (checked) {
      if (!this.selectedSubDepartmentIds.includes(numericId)) {
        this.selectedSubDepartmentIds.push(numericId);
      }
    } else {
      this.selectedSubDepartmentIds = this.selectedSubDepartmentIds.filter(existing => existing !== numericId);
    }

    this.applySubDepartmentFilter();
  }

  toggleAllSubDepartments(selectAll: boolean) {
    if (selectAll) {
      this.selectedSubDepartmentIds = this.subDepartments.map((sub: any) => Number(sub.id));
    } else {
      this.selectedSubDepartmentIds = [];
    }

    this.applySubDepartmentFilter();
  }

  areAllSubDepartmentsSelected(): boolean {
    return this.subDepartments.length > 0 && this.selectedSubDepartmentIds.length === this.subDepartments.length;
  }

  applySubDepartmentFilter() {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    this.CurrentPageNumber = 1;
    this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
  }

  Search() {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    const isAllDepartments = this.isAllDepartmentsSelected();
    const departmentFilter = this.getDepartmentFilterValue();
    const subDepartmentIds =
      isAllDepartments || departmentFilter === this.noDepartmentValue
        ? []
        : this.selectedSubDepartmentIds;

    this.userServ.SearchByNameAndDeptAndSubDep(
      this.selectedName,
      departmentFilter,
      subDepartmentIds,
      isAllDepartments ? { allDepartments: true } : undefined
    ).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber = 1;
        this.filteredUsers = [];
        this.DisplayPagginationOrNot = false;
        this.generatePages();
      },
      () => { }
    );
  }

  getUsersName() {
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        const list = d?.usersNames ?? [];
        this.UsersNames = (Array.isArray(list) ? list : [])
          .map((item: any) => String(item?.name ?? item ?? ''))
          .filter((name) => name.trim() !== '');
      },
      () => { }
    );
  }

  filterByName() {
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      if (this.isAllDepartmentsSelected()) {
        this.loadAllDepartmentsUsers();
      } else {
        this.DisplayPagginationOrNot = true;
        this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
      }
      this.filteredUsers = [];
    } else {
      this.filteredUsers = this.UsersNames.filter(name =>
        name.toLowerCase().includes(query)
      );
    }
  }

  selectUser(location: string) {
    this.selectedName = location;
    const isAllDepartments = this.isAllDepartmentsSelected();
    const departmentFilter = this.getDepartmentFilterValue();
    const subDepartmentIds =
      isAllDepartments || departmentFilter === this.noDepartmentValue
        ? []
        : this.selectedSubDepartmentIds;
    this.userServ.SearchByNameAndDeptAndSubDep(
      this.selectedName,
      departmentFilter,
      subDepartmentIds,
      isAllDepartments ? { allDepartments: true } : undefined
    ).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.DisplayPagginationOrNot = false;
      },
      () => { }
    );
  }

  resetfilteredUsers() {
    this.filteredUsers = [];
  }

  populateYears(): void {
    const startYear = 2019;
    let currentYear = new Date().getFullYear();
    const today = new Date().getDate();
    const currentMonth = new Date().getMonth() + 1;
    if (today > 25 && currentMonth == 12) {
      currentYear++;
    }
    for (let year = startYear; year <= currentYear; year++) {
      this.years.push(year);
    }
  }

  onMonthChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedMonth = target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
    }
  }

  onYearChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedYear = +target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
    }
  }

  saveCurrentPageNumber() {
    localStorage.setItem('HrAttendaceCN', this.CurrentPageNumber.toString());
  }

  private loadAllDepartmentsUsers() {
    this.selectedSubDepartmentIds = [];
    this.pendingSubDepartmentIds = [];
    this.isSubDepartmentDropdownOpen = false;
    this.CurrentPageNumber = 1;
    this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
  }

  private isAllDepartmentsSelected(): boolean {
    return this.selectedDepartment === this.allDepartmentsValue;
  }

  private isNoDepartmentSelected(): boolean {
    return this.selectedDepartment === this.noDepartmentValue;
  }

  private getDepartmentFilterValue(): number | 'none' | null {
    if (this.isAllDepartmentsSelected()) {
      return null;
    }

    if (this.isNoDepartmentSelected()) {
      return this.noDepartmentValue;
    }

    return this.getNumericDepartmentId();
  }

  private getNumericDepartmentId(): number | null {
    return typeof this.selectedDepartment === 'number' ? this.selectedDepartment : null;
  }

  private parseIdList(value: string | null): number[] {
    if (!value) {
      return [];
    }

    return value
      .split(',')
      .map(part => Number(part.trim()))
      .filter(id => !Number.isNaN(id));
  }

  generatePages() {
    this.pages = [];
    for (let i = 1; i <= this.PagesNumber; i++) {
      this.pages.push(i);
    }
  }

  getNextPage() {
    if (this.CurrentPageNumber < this.PagesNumber) {
      this.CurrentPageNumber++;
      this.saveCurrentPageNumber();
      this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
    }
  }

  getPrevPage() {
    if (this.CurrentPageNumber > 1) {
      this.CurrentPageNumber--;
      this.saveCurrentPageNumber();
      this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
    }
  }

  @HostListener('document:click')
  closeSubDepartmentDropdown() {
    this.isSubDepartmentDropdownOpen = false;
  }
}
