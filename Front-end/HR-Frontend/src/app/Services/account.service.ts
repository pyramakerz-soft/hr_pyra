import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { BehaviorSubject } from 'rxjs';
import { jwtDecode } from 'jwt-decode';
// import { MatSnackBar } from '@angular/material/snack-bar';


@Injectable({
  providedIn: 'root'
})
export class AccountService {

  isAuthenticated = !!localStorage.getItem("token"); // Check if token exists

  baseUrl = "http://127.0.0.1:8000/api/auth/login"

  r: { UserName: string, UserType: string, UserId: string, image: string, jobTitle: string } = { UserName: "", UserType: "", UserId: "", image: "", jobTitle: "" };

  constructor(public http: HttpClient, private router: Router) {
    this.CheckToken()
  }

  private CheckToken(): void {
    const token = localStorage.getItem("token");
    if (token) {
      this.isAuthenticated = true;
    } else {
      this.isAuthenticated = false;
    }
  }


  GetDataFromToken(token:string){
   
  }



  Login(email: string, password: string) {
    console.log(email);
    console.log(password);
    const body = { email, password };
    this.http.post(`${this.baseUrl}`, body, { responseType: 'text' })
      .subscribe((d: string) => {
        const response = JSON.parse(d);
        const token = response.token;
        console.log(token);

        if (token) {
          this.isAuthenticated = true;
          localStorage.setItem("token", token);
          this.router.navigateByUrl("employee");

        }
      },
        (error: HttpErrorResponse) => {
          if (error.status === 401) {
            // Show an alert for invalid email or password
            alert('Invalid email or password');
          } else if (email === "" && password !== "") {
            alert('Email cannot be empty');
          } else if (password === "" && email !== "") {
            alert('Password cannot be empty');
          } else {
            alert('Please enter the data');
          }
        }
      );
  }



}
