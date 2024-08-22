import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { UserServiceService } from '../../../Services/user-service.service';
import { AddEmployee } from '../../../Models/add-employee';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-hr-employee-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-employee-details.component.html',
  styleUrl: './hr-employee-details.component.css'
})
export class HrEmployeeDetailsComponent {
  empId:number|null = null
  employee: AddEmployee = new AddEmployee(
    '', '', 0, '', '', '', '', '', '', new Date(), 0, 0, '', new Date(), new Date(), '', []
  );

  constructor(public router:Router, public activeRoute:ActivatedRoute, public userService:UserServiceService){}

  ngOnInit(): void {
    this.activeRoute.params.subscribe(params => {
      this.empId = +params['EmpId'];
      this.getEmployeeByID(this.empId)
    });
  }

  getEmployeeByID(id:number){
    this.userService.getUserById(id).subscribe(
      (d: any) => {
        this.employee = d.User;
        console.log(this.employee)
      },
      (error) => {
        console.log(error)
      }
    );
  }

  NavigateToEditEmployee(empId:number|null){
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }
  
}
