import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, ParamMap, Router } from '@angular/router';
import { Department } from '../../../Models/department';
import { UserModel } from '../../../Models/user-model';
import { ClockService } from '../../../Services/clock.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { UserServiceService } from '../../../Services/user-service.service';

@Component({
  selector: 'app-hr-attendance',
  standalone: true,
  imports: [CommonModule , FormsModule],
  templateUrl: './hr-attendance.component.html',
  styleUrl: './hr-attendance.component.css'
})
export class HrAttendanceComponent {
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
  selectedSubDepartment: number | null = null;
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
    public supDeptServ: SubDepartmentService
  ) {}

  ngOnInit() {
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
      }, error => {
        this.isLoading = false;
        alert('Failed to export data.');
      });
  }

  ExportAbsentUserData() {
    this.isLoading = true;
    this.clockService.ExportAbsentUserData(this.from_day, this.to_day).subscribe(
      (result: Blob) => {
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `absent_users.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      (error) => {
        this.isLoading = false;
        console.error('Error exporting user data:', error);
      }
    );
    this.isLoading = false;
  }

  NavigateToEmployeeAttendanceDetails(EmpId: number) {
    this.router.navigateByUrl("HR/HRAttendanceEmployeeDetails/" + EmpId)
  }

  private applyQueryParams(params: ParamMap): void {
    const departmentIdParam = params.get('departmentId');
    const noDepartmentParam = params.get('noDepartment');
    const allDepartmentsParam = params.get('allDepartments');

    if (departmentIdParam) {
      const parsedId = Number(departmentIdParam);
      if (!Number.isNaN(parsedId)) {
        this.selectedDepartment = parsedId;
        this.selectedSubDepartment = null;
        this.subDepartments = [];
        this.supDeptServ.setDeptId(parsedId);
        this.getSubDepartments(parsedId);
        this.CurrentPageNumber = 1;
        return;
      }
    }

    if (noDepartmentParam === '1') {
      this.selectedDepartment = this.noDepartmentValue;
      this.selectedSubDepartment = null;
      this.subDepartments = [];
      this.CurrentPageNumber = 1;
      return;
    }

    if (allDepartmentsParam === '1') {
      this.selectedDepartment = this.allDepartmentsValue;
      this.selectedSubDepartment = null;
      this.subDepartments = [];
      this.CurrentPageNumber = 1;
    }
  }

  getAllEmployees(pgNumber: number, from_day: string = '', to_day: string = '') {
    this.CurrentPageNumber = pgNumber;
    this.saveCurrentPageNumber();
    const departmentFilter = this.getDepartmentFilterValue();
    const options: { allDepartments?: boolean; departmentId?: number | 'none'; subDepartmentId?: number | null } = {};

    if (this.isAllDepartmentsSelected()) {
      options.allDepartments = true;
    } else if (departmentFilter === this.noDepartmentValue) {
      options.departmentId = this.noDepartmentValue;
    } else if (typeof departmentFilter === 'number') {
      options.departmentId = departmentFilter;
      if (this.selectedSubDepartment != null) {
        options.subDepartmentId = this.selectedSubDepartment;
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
      (error) => { }
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
    this.selectedUsers = []
    this.isSelectAllChecked = false;
    this.subDepartments = [];
    this.selectedSubDepartment = null;
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
      this.getSubDepartments(this.selectedDepartment);
    }

    this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
  }

  getSubDepartments(departmentId: number) {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    this.supDeptServ.getall(departmentId).subscribe(
      (res: any) => {
        this.subDepartments = res.data || res;
      },
      (err) => {
        console.error('Failed to fetch sub-departments', err);
      }
    );
  }

  onSubDepartmentChange() {
    this.selectedUsers = []
    this.isSelectAllChecked = false;
    this.Search();
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
    this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.saveCurrentPageNumber();
    this.getAllEmployees(this.CurrentPageNumber, this.from_day, this.to_day);
  }

  Search() {
    this.selectedUsers = [];
    this.isSelectAllChecked = false;
    const isAllDepartments = this.isAllDepartmentsSelected();
    const departmentFilter = this.getDepartmentFilterValue();
    const subDepartmentId =
      isAllDepartments || departmentFilter === this.noDepartmentValue
        ? null
        : this.selectedSubDepartment;
    this.userServ.SearchByNameAndDeptAndSubDep(
      this.selectedName,
      departmentFilter,
      subDepartmentId,
      isAllDepartments ? { allDepartments: true } : undefined
    ).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber = 1;
        this.filteredUsers = [];
        this.DisplayPagginationOrNot = false;
        this.generatePages();
      },
      (error) => {}
    );
  }

  getUsersName() {
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        this.UsersNames = d.usersNames;
      },
      (error) => {}
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
    const subDepartmentId =
      isAllDepartments || departmentFilter === this.noDepartmentValue
        ? null
        : this.selectedSubDepartment;
    this.userServ.SearchByNameAndDeptAndSubDep(
      this.selectedName,
      departmentFilter,
      subDepartmentId,
      isAllDepartments ? { allDepartments: true } : undefined
    ).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.DisplayPagginationOrNot = false;
      },
      (error) => {}
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
}

