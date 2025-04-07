import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Department } from '../../../Models/department';
import { UserModel } from '../../../Models/user-model';
import { ClockService } from '../../../Services/clock.service';
import { DepartmentService } from '../../../Services/department.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { UserServiceService } from '../../../Services/user-service.service';

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
  from_day: string = '';
  to_day: string = '';  


  selectedUsers: { userId: number, userName: string }[] = [];



  selectedMonth: string = "01";
  selectedYear: number = 0;
  SelectDepartment:string="AllDepartment";
  departments:Department[]=[]
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
  
  selectedDepartment: number | null = null;
  selectedSubDepartment: number | null = null;


  constructor(public router:Router , public userServ:UserServiceService, public UserClocksService: ClockService ,private clockService:ClockService , public departmentServ: DepartmentService,
public supDeptServ:SubDepartmentService

  ){}

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


  // Method to handle "Select All" checkbox state change
  toggleSelectAll() {
    this.selectedUsers=[]

    if(!this.isSelectAllChecked){
      this.selectedUsers=[]
      this.isSelectAllChecked=false;
    }else
   { // Set all users' selected state to match "Select All" checkbox
    this.tableData.forEach(row => {
      console.log('/ssffff');
      
      this.selectedUsers.push({ userId: row.id, userName: row.name });
      this.isSelectAllChecked=true;

    });}
  }

// Method to check if the user is selected
isUserSelected(userId: number): boolean {
  return this.selectedUsers.some(u => u.userId === userId);
}
  // Method to handle checkbox selection change
  onUserSelectionChange(row: any): void {
    
    if (row.selected) {
      // If selected, add user to the selectedUsers array
      this.selectedUsers.push({ userId: row.id, userName: row.name });
    } else {
      // If not selected, remove user from the selectedUsers array
      this.selectedUsers = this.selectedUsers.filter(u => u.userId !== row.id);
    }
  }


ExportData(){
  
  this.selectedUsers.forEach(user => {
    this.isLoading=true;
    this.clockService.ExportUserDataById(user.userId, this.from_day, this.to_day).subscribe(
      (result: Blob) => {
        const userName = user.userName; // Get the user name from selectedUsers
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${userName}_ClockIn_${this.from_day}_to_${this.to_day}.xlsx`; // Use the userName for the file name
        a.click();
        window.URL.revokeObjectURL(url);
      },

      (error) => {
        this.isLoading=false;

        console.error('Error exporting user data:', error);
      }
    );
  });
      this.isLoading=false;

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
        this.PagesNumber = d.data.pagination?.last_page || 1; // Check for last_page and default to 1 if not available
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





onDepartmentChange() {
  this.selectedUsers=[]
  this.isSelectAllChecked=false;
 this.subDepartments = [];
 console.log(this.selectedDepartment);
 this.selectedSubDepartment=null;
 if (this.selectedDepartment) {
   this.getSubDepartments(this.selectedDepartment);
   this.Search()
 }
}

getSubDepartments(departmentId: number) {
 this.supDeptServ.getall (departmentId).subscribe(
   (res: any) => {
     this.subDepartments = res.data || res;
   },
   (err) => {
     console.error('Failed to fetch sub-departments', err);
   }
 );
}

onSubDepartmentChange() {
  this.selectedUsers=[]
  this.isSelectAllChecked=false;

 this.Search(); // optionally trigger search/filter
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
    // if(this.selectedName){
    this.userServ.SearchByNameAndDeptAndSubDep(this.selectedName,this.selectedDepartment,this.selectedSubDepartment).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber=1;
        // this.DisplayPagginationOrNot=false;
        this.filteredUsers=[];




        this.tableData = d.data.users;
        this.PagesNumber = d.data.pagination?.last_page || 1; // Check for last_page and default to 1 if not available
        this.generatePages();
      
      

      },
      (error) => {
      }
    );
  // }
  // else{
  //   this.DisplayPagginationOrNot=true;
  // }
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
    this.userServ.SearchByNameAndDeptAndSubDep(this.selectedName).subscribe(
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


  // onDepartmentChange(event: Event): void {
  //   const target = event.target as HTMLSelectElement;
  //   if (target) {
  //     this.SelectDepartment = target.value; 
  //   }

  // }


  
  saveCurrentPageNumber() {
    localStorage.setItem('HrAttendaceCN', this.CurrentPageNumber.toString());
  }
}
