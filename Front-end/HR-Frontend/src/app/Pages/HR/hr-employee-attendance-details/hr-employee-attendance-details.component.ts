import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';
import { EmployeeDashService } from '../../../Services/employee-dash.service';
import { ClockService } from '../../../Services/clock.service';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { UserServiceService } from '../../../Services/user-service.service';



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
  employee:any
  isDateSelected = false


  constructor(public empDashserv: EmployeeDashService, public UserClocksService: 
    ClockService, public activatedRoute: ActivatedRoute, public userService:UserServiceService,
    public route: Router) { }


  ngOnInit() {
    this.activatedRoute.params.subscribe({
      next: (params) => {
        const id = params['Id'];
        this.UserID = id;
        if (id) {
          this.getAllClocks(1);
          this.generatePages();
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

  getEmployeeByID(id:number){
    this.userService.getUserById(id).subscribe(
      (d: any) => {
        this.employee = d.User;
      },
      (error) => {
        console.log(error)
      }
    );
  }


  getAllClocks(PgNumber: number) {
    this.UserClocksService.GetUserClocksById(this.UserID, PgNumber).subscribe(
      (d: any) => {
        this.tableData = d.data.clocks;
        this.PagesNumber = d.data.pagination.last_page;
      },
      (error) => {
        console.log(error)
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
    this.showOtherClocks = !this.showOtherClocks;
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
      this.DisplayPagginationOrNot = true;  // Ensure your logic here
    }
  }

  EditUserClock(Clock:EmployeeDashboard) {
    this.route.navigate(['HR/HREmployeeAttendanceEdit'], { state: { data: Clock } }); // Pass data via router state
  }

  ClearSearch(){
    this.isDateSelected = false
    this.SelectedDate = ''
    if (this.UserID) {
      this.getAllClocks(1);
      this.generatePages();
    }
  }

  openDialog(){
    
  }
}
