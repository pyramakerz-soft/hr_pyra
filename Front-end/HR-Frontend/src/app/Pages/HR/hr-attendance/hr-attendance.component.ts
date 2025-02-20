import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';
import { FormsModule } from '@angular/forms';
import { ClockService } from '../../../Services/clock.service';
import Swal from 'sweetalert2';
import { DepartmentService } from '../../../Services/department.service';
import { Department } from '../../../Models/department';

interface data{
  Employees:string,
  Department:string,
  Position:string,
}

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
  DisplayPagginationOrNot:boolean=true;
  UsersNames:string[]=[];
  filteredUsers: string[] = [];

  loading: boolean = false; 
  errorMessage: string = '';
  isLoading: boolean = false; // Track loading state



  selectedMonth: string = "01";
  selectedYear: number = 0;
  SelectDepartment:string="AllDepartment";
  departments:Department[]=[]
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


  constructor(public router:Router , public userServ:UserServiceService, public UserClocksService: ClockService , public departmentServ: DepartmentService){}

  tableData:UserModel[]= [];

  ngOnInit(){
    const savedPageNumber = localStorage.getItem('HrAttendaceCN');
    if (savedPageNumber) {
      this.CurrentPageNumber = parseInt(savedPageNumber, 10);
    } else {
      this.CurrentPageNumber = 1; // Default value if none is saved
    }
    this.getAllEmployees(this.CurrentPageNumber);
    this.getUsersName();
    this.GetAllDepartment()
    this.populateYears();
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1
    this.selectedMonth = currentMonth < 10 ? `0${currentMonth}` : `${currentMonth}`;
    this.selectedYear = currentDate.getFullYear();
    this.SelectDepartment="AllDepartment";
    this.DateString = this.selectedYear + "-" + this.selectedMonth
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");

  }

  NavigateToEmployeeAttendanceDetails(EmpId:number){
    this.router.navigateByUrl("HR/HRAttendanceEmployeeDetails/"+EmpId)
  }


  getAllEmployees(pgNumber:number) {
    this.CurrentPageNumber = pgNumber;
    this.saveCurrentPageNumber();
    this.userServ.getall(pgNumber).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber=d.data.pagination.last_page;
        this.generatePages();
      },
      (error) => {
      }
    );
  }

  GetAllDepartment(){
    this.departmentServ.getall().subscribe(
      (d: any) => {
        this.departments = d.data.departments;
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

  Search(){
    if(this.selectedName){
    this.userServ.SearchByName(this.selectedName).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber=1;
        this.DisplayPagginationOrNot=false;
        this.filteredUsers=[];
      },
      (error) => {
      }
    );
  }
  else{
    this.DisplayPagginationOrNot=true;
  }
  }
  

  getUsersName(){
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        this.UsersNames=d.usersNames;
      },
      (error) => {
      }
    );
  }


  filterByName() {
    // this.getLocationsName();
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      // If the input is empty, call getAllLocations with the current page number
      this.getAllEmployees(this.CurrentPageNumber);
      this.DisplayPagginationOrNot=true;
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
    this.userServ.SearchByName(this.selectedName).subscribe(
      (d: any) => {
        this.tableData=d.data.users;
        this.DisplayPagginationOrNot=false;
      },
      (error) => {

      }
    );

  }

  resetfilteredUsers(){
    this.filteredUsers = [];

  }

  populateYears(): void {
    const startYear = 2019;
    let currentYear = new Date().getFullYear();
    const today = new Date().getDate();
    const currentMonth = new Date().getMonth() + 1;
    // console.log(today , currentMonth)
    if(today>25&&currentMonth==12){
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


  onDepartmentChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.SelectDepartment = target.value; 
    }

  }

 ExportData() {
   this.isLoading = true; // Show spinner
    this.UserClocksService.ExportAllUserDataById(this.DateString, this.SelectDepartment).subscribe(
      (result: Blob) => {
        this.isLoading = false; // Hide spinner
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Employees_ClockIn.xlsx`; 
        a.click();
        window.URL.revokeObjectURL(url);
      },
    (error) => {
      this.loading = false; // Hide loading spinner
      if (error.status === 404) {
        Swal.fire({   
          text: "No attendance records found for this date.",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",
        });
      } else {
        this.errorMessage = "An error occurred while exporting data. Please try again.";
      }
    }
  );
}

  
  saveCurrentPageNumber() {
    localStorage.setItem('HrAttendaceCN', this.CurrentPageNumber.toString());
  }
}
