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
    null, '', '', null, '', '', '', '', '', '', null, null, null, null, null, null, '', [], [1], [], [], [], false);

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
        d.User.start_time = this.convertUTCToEgyptLocalTime(d.User.start_time)
        d.User.end_time = this.convertUTCToEgyptLocalTime(d.User.end_time)

        this.employee = d.User;
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

  convertUTCToEgyptLocalTime(utcTimeStr: string): string {
    const [time, period] = utcTimeStr.split(/(AM|PM)/);
    let [hours, minutes] = time.split(':').map(Number);
    if (period === 'PM' && hours !== 12) {
      hours += 12;
    }
    if (period === 'AM' && hours === 12) {
      hours = 0;
    }
    const currentDate = new Date();
    const utcDate = new Date(Date.UTC(currentDate.getUTCFullYear(), currentDate.getUTCMonth(), currentDate.getUTCDate(), hours, minutes));
    const egyptTimeZone = 'Africa/Cairo';
    const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: egyptTimeZone }));
    let localHours = localDate.getHours();
    const localMinutes = localDate.getMinutes();
    const localPeriod = localHours >= 12 ? 'PM' : 'AM';
    localHours = localHours % 12 || 12; // Converts '0' hours to '12'
    const formattedHours = String(localHours).padStart(2, '0');
    const formattedMinutes = String(localMinutes).padStart(2, '0');
    return `${formattedHours}:${formattedMinutes} ${localPeriod}`;
  }

}
