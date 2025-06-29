import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AccountService } from '../../Services/account.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule, 
    FormsModule,
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {

  email:string = ""
  password:string = ""

  emailError: string = ""; 
  passwordError: string = ""; 

  constructor(private router: Router , public accountService:AccountService){  }

  isValidEmail(email: string): boolean {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
  }

  isFormValid(){
    let isValid = true
    this.emailError = "";  
    this.passwordError = ""; 

    if (this.email.trim() === "" && this.password.trim() === "") {
      isValid = false;
      this.emailError = '*Email cannot be empty';
      this.passwordError = '*Password cannot be empty';
    } else if (this.email.trim() === "") {
      isValid = false;
      this.emailError = '*Email cannot be empty';
    } else if (this.password.trim() === "") {
      isValid = false;
      this.passwordError = '*Password cannot be empty';
    } 
    if (!this.isValidEmail(this.email) && this.email.trim() !== ""){
      this.emailError = '*Invalid Email';
      isValid = false;
    }
    return isValid
  }

  onEmailChange() {
    this.emailError = "" 
  }

  onPasswordChange() {
    this.passwordError = "" 
  }
  
  SignIn(){
    if(this.isFormValid()){
      this.accountService.Login(this.email,this.password).subscribe(
        async (d: string) => {
            const response = JSON.parse(d);
            const token = response.token;
  
            if (token) {
              this.accountService.isAuthenticated = true;
              localStorage.setItem("token", token);
  
              // Wait for the data fetch to complete
              const userData = await this.accountService.GetDataFromToken().toPromise();
  
              const userResponse = JSON.parse(userData as string);
              this.accountService.r = userResponse.User;
              localStorage.setItem("role", userResponse.User.role_name);

              // Navigate based on role
              if (this.accountService.r.role_name === "Employee") {
                this.router.navigateByUrl("employee");
              } else if (this.accountService.r.role_name === "Hr"  ||  this.accountService.r.role_name === "Admin"  ||  this.accountService.r.role_name === "Team leader" ) {
                this.router.navigateByUrl("HR");
              }
            }
          
        },
        (error: HttpErrorResponse) => {
          if (error.error.includes("Wrong Email")) {
            this.emailError = '*Wrong Email'
          }else if (error.error.includes("Wrong password")) {
            this.passwordError = '*Wrong Password';
          } else if(error.status === 400) {
            this.passwordError = '*Password Is Less then 6 Chars';
          } else if(error.status === 500){
            Swal.fire({ 
              title: "Server is Not Available Please try in another time", 
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
            });
          }
        }
      );
    }
  }
}
