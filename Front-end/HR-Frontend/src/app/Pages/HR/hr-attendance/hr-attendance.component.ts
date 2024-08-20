import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';

interface data{
  Employees:string,
  Department:string,
  Position:string,
}

@Component({
  selector: 'app-hr-attendance',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-attendance.component.html',
  styleUrl: './hr-attendance.component.css'
})
export class HrAttendanceComponent {
  constructor(public router:Router){}

  tableData:data[]= [
    { Employees: "Aya Atiea", Department: "Software", Position: "Senior UI/UX Designer" },
    { Employees: "Aya Atiea", Department: "Software", Position: "Senior UI/UX Designer" },
    { Employees: "Aya Atiea", Department: "Software", Position: "Senior UI/UX Designer" },
    // Add more data as needed
  ];

  NavigateToEmployeeAttendanceDetails(){
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails")
  }
}
