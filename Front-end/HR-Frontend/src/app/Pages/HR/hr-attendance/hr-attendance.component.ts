import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';

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
  constructor(public router:Router , public userServ:UserServiceService){}

  tableData:UserModel[]= [];

  ngOnInit(){
    this.getAllEmployees();
  }

  NavigateToEmployeeAttendanceDetails(EmpId:number){
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/"+EmpId)
  }

  getAllEmployees() {
    this.userServ.getall().subscribe(
      (d: any) => {
        this.tableData = d.data.users;
      },
      (error) => {
        console.log(error)
      }
    );
  }

}
