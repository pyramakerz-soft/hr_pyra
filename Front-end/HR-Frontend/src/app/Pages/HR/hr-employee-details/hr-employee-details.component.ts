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
    null, '', '', null, null, '', '', '', '', '', '', null, null, null, null, null, null, '', [], [1], [], [], [], false, 0);

  password:string =""
  PasswordError: string = ""; 
  isChange = false;
  has_serial_number = false;

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
        d.User.start_time = this.convertTimeFormate(d.User.start_time)
        d.User.end_time = this.convertTimeFormate(d.User.end_time)

        this.employee = d.User;
        this.userService.checkSerialNumber(d.User.id).subscribe(
          (d:any) => {
            this.has_serial_number = d.has_serial_number
          }
        )
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

  convertTimeFormate(TimeStr: string): string {
    let [hours, minutes] = TimeStr.split(':').map(Number);
    const localPeriod = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    const formattedHours = String(hours).padStart(2, '0');
    const formattedMinutes = String(minutes).padStart(2, '0');
    return `${formattedHours}:${formattedMinutes} ${localPeriod}`;
  }

  DeleteSerialNum(){
    Swal.fire({
      title: 'Are you sure you want to delete serial number?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if(this.empId){
        this.userService.DeleteSerialNum(this.empId).subscribe(
          (d:any) => {
            this.has_serial_number = false
          }
        )
      }
    });
  }
}
