import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';
import { EmployeeDashService } from '../../../Services/employee-dash.service';
import { ClockService } from '../../../Services/clock.service';
import { ActivatedRoute, Router } from '@angular/router';



@Component({
  selector: 'app-hr-employee-attendance-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-employee-attendance-details.component.html',
  styleUrl: './hr-employee-attendance-details.component.css'
})
export class HrEmployeeAttendanceDetailsComponent {
  tableData:EmployeeDashboard[]= [];
  token:string="";
  showOtherClocks: boolean= false; 
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  constructor(public empDashserv:EmployeeDashService , public UserClocksService : ClockService ,public activatedRoute: ActivatedRoute,
    public route: Router){}


    ngOnInit() {
      this.activatedRoute.params.subscribe({
        next: (params) => {
          const id = params['Id']; 
          if (id) {
            this.getAllClocks(id);
          } else {
            console.error('No ID found in route parameters');
          }
        },
        error: (err) => {
          console.error('Error in route parameters:', err);
        }
      });
    }
    

  getAllClocks(id:number) {
    this.UserClocksService.GetUserClocksById(id).subscribe(
      (d: any) => {
        console.log(d)
        this.tableData = d.data.clocks;
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
}
