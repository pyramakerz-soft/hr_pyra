import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-hr-employee-details',
  standalone: true,
  imports: [],
  templateUrl: './hr-employee-details.component.html',
  styleUrl: './hr-employee-details.component.css'
})
export class HrEmployeeDetailsComponent {
  constructor(public router:Router){}

  NavigateToEditEmployee(empId:number){
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }
}
