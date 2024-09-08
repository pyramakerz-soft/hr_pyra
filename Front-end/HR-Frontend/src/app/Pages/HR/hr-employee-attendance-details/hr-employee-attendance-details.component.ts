import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';
import { EmployeeDashService } from '../../../Services/employee-dash.service';
import { ClockService } from '../../../Services/clock.service';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { UserServiceService } from '../../../Services/user-service.service';
import { MatDialog } from '@angular/material/dialog';
import { ClockInPopUpComponent } from '../../../Components/clock-in-pop-up/clock-in-pop-up.component';
import { AddEmployee } from '../../../Models/add-employee';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-employee-attendance-details',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-employee-attendance-details.component.html',
  styleUrl: './hr-employee-attendance-details.component.css'
})
export class HrEmployeeAttendanceDetailsComponent {
  tableData: EmployeeDashboard[] = [];
  token: string = "";
  showOtherClocks: boolean = false;
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  UserID: number = 1;
  DisplayPagginationOrNot: boolean = true;
  SelectedDate: string = ""
  employee: AddEmployee = new AddEmployee(
    null, '', '', null, '', '', '', '', '', '', null, null, null, null, null, null, '', [], [1], [], [], [], false);

  isDateSelected = false
  rowNumber: boolean[] = [];

  selectedMonth: string = "01";
  selectedYear: number = 0;
  DateString: string = "2019-01";

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

  constructor(public empDashserv: EmployeeDashService, public UserClocksService:
    ClockService, public activatedRoute: ActivatedRoute, public userService: UserServiceService,
    public route: Router, public dialog: MatDialog) { }


  ngOnInit() {
    this.populateYears();
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1
    this.selectedMonth = currentMonth < 10 ? `0${currentMonth}` : `${currentMonth}`;
    this.selectedYear = currentDate.getFullYear();
    this.DateString = this.selectedYear + "-" + this.selectedMonth

    this.activatedRoute.params.subscribe({
      next: (params) => {
        const id = params['Id'];
        this.UserID = id;
        if (id) {
          this.getAllClocks(1);
          this.getEmployeeByID(id)
        } else {
          console.error('No ID found in route parameters');
        }
      },
      error: (err) => {
        console.error('Error in route parameters:', err);
      }
    });
  }

  populateYears(): void {
    const startYear = 2019;
    const currentYear = new Date().getFullYear();

    for (let year = startYear; year <= currentYear; year++) {
      this.years.push(year);
    }
  }

  onMonthChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedMonth = target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
      this.tableData = []
      this.getAllClocks(1)
    }
  }

  onYearChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedYear = +target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
      this.tableData = []
      this.getAllClocks(1)
    }
  }

  getFormattedMonthYear(): string {
    const monthName = this.months.find(m => m.value === this.selectedMonth)?.name;
    return monthName ? `${monthName} ${this.selectedYear}` : `${this.selectedYear}`;
  }

  convertUTCToEgyptLocalTime(utcTimeStr: string): string {
    const [time, period] = utcTimeStr.split(/(AM|PM)/);
    let [hours, minutes] = time.split(':').map(Number);
    if (period === 'PM' && hours !== 12) {
      hours += 12;
    }
    if (period === 'AM' && hours === 12) {
      hours = 0;
    }
    const currentDate = new Date();
    const utcDate = new Date(Date.UTC(currentDate.getUTCFullYear(), currentDate.getUTCMonth(), currentDate.getUTCDate(), hours, minutes));
    const egyptTimeZone = 'Africa/Cairo';
    const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: egyptTimeZone }));
    let localHours = localDate.getHours();
    const localMinutes = localDate.getMinutes();
    const localPeriod = localHours >= 12 ? 'PM' : 'AM';
    localHours = localHours % 12 || 12; // Converts '0' hours to '12'
    const formattedHours = String(localHours).padStart(2, '0');
    const formattedMinutes = String(localMinutes).padStart(2, '0');
    return `${formattedHours}:${formattedMinutes} ${localPeriod}`;
  }

  getEmployeeByID(id: number) {
    this.activatedRoute.data.subscribe(
      (data) => {
        this.employee = data['user'].User;
      }
    )

  }


  getAllClocks(PgNumber: number) {
    this.CurrentPageNumber=PgNumber
    this.UserClocksService.GetUserClocksById(this.UserID, PgNumber, this.DateString).subscribe(
      (d: any) => {
        this.tableData = d.data.clocks;

        this.rowNumber = new Array(this.tableData.length).fill(false);
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
    this.getAllClocks(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.getAllClocks(this.CurrentPageNumber);

  }

  toggleOtherClocks(index: number): void {

    this.rowNumber[index] = !this.rowNumber[index];
    this.showOtherClocks = this.rowNumber.some(state => state);
  }

  searchByDate() {
    if (this.SelectedDate) {
      this.UserClocksService.SearchByDate(this.UserID, this.SelectedDate).subscribe(
        (d: any) => {
          this.tableData = d.data.clocks;
          this.PagesNumber = 1;
          this.DisplayPagginationOrNot = false;

        },
        (error) => {
          this.tableData = [];
          this.PagesNumber = 1;
          this.DisplayPagginationOrNot = false;
        }
      );
      this.isDateSelected = true
    } else {
      this.DisplayPagginationOrNot = true;
    }
  }

  EditUserClock(Clock: any) {

    this.route.navigate(['HR/HREmployeeAttendanceEdit/'+Clock.id], { state: { data: Clock, UserId: this.UserID } });
  }

  ClearSearch() {
    this.isDateSelected = false
    this.SelectedDate = ''
    if (this.UserID) {
      this.getAllClocks(1);
      this.generatePages();
    }
  }

  openDialog() {

    const dialogRef = this.dialog.open(ClockInPopUpComponent, {
      data: { Name: this.employee.name, job_title: this.employee.emp_type, work_home: this.employee.work_home, isClockInFromHrToOtherUser: true, userId: this.UserID }

    });
    dialogRef.afterClosed().subscribe(result => {
      if (result !== undefined) {
        this.getAllClocks(1)
      }
    });
  }

  ExportData() {
    this.UserClocksService.ExportUserDataById(this.UserID, this.DateString).subscribe(
      (result: Blob) => {
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${this.employee.name}_ClockIn.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      (error) => {

        if (error.status == 404) {
          Swal.fire({
            text: "There are no clock in for this User at this Date",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        }
      }
    );
  }

  hasOtherClocks(otherClocks: { [key: number]: any }): boolean {
    return Object.keys(otherClocks).length > 0;
  }
}
