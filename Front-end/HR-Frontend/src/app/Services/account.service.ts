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
      this.GetDataFromToken().subscribe(
        (d: string) => {
          try {
            const response = JSON.parse(d);
            const userDetails = response.User;
            this.r = userDetails;
            console.log(this.r);
          } catch (error) {
            console.error('Error parsing JSON response:', error);
          }
        },
        (error: HttpErrorResponse) => {
          console.error('Error fetching data from token:', error);
        }
      );
    } else {
      this.isAuthenticated = false;
    }
  }
  
  GetDataFromToken() {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    return this.http.get(`${this.baseUrl}/user_by_token`, { headers, responseType: 'text' });
  }
  
  async Login(email: string, password: string) {
    // Validate inputs
    if (email.trim() === "" || password.trim() === "") {
      if (email.trim() === "" && password.trim() === "") {
        alert('Please enter the data');
      } else if (email.trim() === "" && password.trim() !== "") {
        alert('Email cannot be empty');
      } else if (password.trim() === "" && email.trim() !== "") {
        alert('Password cannot be empty');
      }
      return;
    }
  
    const body = { email, password };
    this.http.post(`${this.baseUrl}/login`, body, { responseType: 'text' })
      .subscribe(
        async (d: string) => {
          try {
            const response = JSON.parse(d);
            const token = response.token;
  
            if (token) {
              this.isAuthenticated = true;
              localStorage.setItem("token", token);
  
              // Wait for the data fetch to complete
              const userData = await this.GetDataFromToken().toPromise();
  
              try {
                const userResponse = JSON.parse(userData as string);
                this.r = userResponse.User;
                console.log(this.r, token);
  
                // Navigate based on role
                if (this.r.role_name === "Employee") {
                  this.router.navigateByUrl("employee");
                } else if (this.r.role_name === "Hr") {
                  this.router.navigateByUrl("HR");
                }
  
              } catch (error) {
                console.error('Error parsing user data:', error);
              }
            }
          } catch (error) {
            console.error('Error parsing login response:', error);
          }
        },
        (error: HttpErrorResponse) => {
          if (error.status === 401) {
            alert('Invalid email or password');
          } else {
            alert('An unexpected error occurred');
          }
        }
      );
  }

  logout() {
    this.isAuthenticated = false;
    localStorage.removeItem("token");
    // Optional: Redirect to login or home page
    this.router.navigateByUrl("/login");
  }
}  
