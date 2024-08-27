import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { UserServiceService } from '../../../Services/user-service.service';
import { AddEmployee } from '../../../Models/add-employee';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-hr-employee-details',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-employee-details.component.html',
  styleUrl: './hr-employee-details.component.css'
})
export class HrEmployeeDetailsComponent {
  empId:number|null = null
  employee: AddEmployee = new AddEmployee(
    null, '', '', null, '', '', '', '', '', '', null, null, null, null, null, null, '', [], [1], [], [], []
  );

  password:string =""
  PasswordError: string = ""; 
  isChange = false;

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
      },
      (error) => {
        console.log(error)
      }
    );
  }

  NavigateToEditEmployee(empId:number|null){
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }

  toggleChangePassword() {
    this.isChange = !this.isChange;
  }

  isFormValid(){
    let isValid = true
    this.PasswordError = "";  

    if (this.password.trim() === "") {
      isValid = false;
      this.PasswordError = '*Password is Required';
    } else if (this.password.length < 6) {
      isValid = false;
      this.PasswordError = '*Password Should be more than 6 characters';
    } 
    return isValid
  }

  onPasswordChange() {
    this.PasswordError = "" 
  }
  
  UpdatePassword(){
    if(this.isFormValid() && this.empId){
      this.userService.updatePassword(this.password,this.empId).subscribe(
        (d: any) => {
          Swal.fire({   
            title: "Updated Successfully",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
          this.isChange = false
          this.password = '';
        },
        () => {
          Swal.fire({   
            text: "Faild to create, Please Try again later",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
            
          });
        }
      );
    } 
  }

  CancelUpdatePassword(){
    this.isChange = false
    this.password = '';
  }
}
