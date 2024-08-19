import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { EmployeeDashService } from '../../../Services/employee-dash.service';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';

@Component({
  selector: 'app-table',
  standalone: true,
  imports: [CommonModule,FormsModule],
  templateUrl: './table.component.html',
  styleUrl: './table.component.css'
})
export class TableComponent {
  token:any="";
  Userclocks:EmployeeDashboard[]=[];
  pageNumber=1;


  constructor(public empDashserv:EmployeeDashService){
  }

  ngOnInit(): void {
    this.GetClockss(1);
  }

 
  GetClockss(pgNumb:number){
    this.pageNumber=pgNumb;
    this.token = localStorage.getItem("token");
    this.empDashserv.GetClocks(this.token,pgNumb).subscribe(
     (d: any) => {
       this.Userclocks = d.clocks; 
     },
     (error) => {
       console.error('Error retrieving user clocks:', error);
     }
   );
  }

  getNextClocks(): void {
    this.pageNumber++;
    this.GetClockss(this.pageNumber);
  }

  getPrevClocks(): void {
    this.pageNumber--;
    this.GetClockss(this.pageNumber);
  }

}
