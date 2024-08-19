import { HttpClient, HttpErrorResponse, HttpHeaders, HttpParams } from '@angular/common/http';
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

  baseUrl = "http://127.0.0.1:8000/api/auth"

  r: { name: string, job_title: string, id: string, image: string, role_name: string , is_clocked_out :string ,national_id:string, clockIn:string} = { name: "", job_title: "", id: "", image: "",role_name:"" , is_clocked_out :"",national_id:"" ,clockIn:""};

  constructor(public http: HttpClient, private router: Router) {
    this.CheckToken();
  }

  private CheckToken(): void {
    const token = localStorage.getItem("token");
    if (token) {
      this.isAuthenticated = true;
      this.GetDataFromToken().subscribe((d: string) => {
        try {
          const response = JSON.parse(d);
          const userDetails = response.User;
          this.r = userDetails;
        } catch (error) {
          console.error('Error parsing JSON response:', error);
        }
      });
    } else {
      this.isAuthenticated = false;
    }
  }


  GetDataFromToken() {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
   return this.http.get(`${this.baseUrl}/user_by_token`, { headers, responseType: 'text' });
  }


  


  Login(email: string, password: string) {
    const body = { email, password };
    this.http.post(`${this.baseUrl}/login`, body, { responseType: 'text' })
      .subscribe((d: string) => {
        const response = JSON.parse(d);
        const token = response.token;

        if (token) {
          this.isAuthenticated = true;
          localStorage.setItem("token", token);
          this.GetDataFromToken();
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
