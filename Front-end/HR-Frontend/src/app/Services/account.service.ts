import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';


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

  public CheckToken(): void {
    const token = localStorage.getItem("token");
    if (token) {
      this.isAuthenticated = true;
      this.GetDataFromToken().subscribe(
        (d: string) => {
          try {
            const response = JSON.parse(d);
            const userDetails = response.User;
            this.r = userDetails;
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
  
  Login(email: string, password: string) {
    const body = { email, password };
    return this.http.post(`${this.baseUrl}/login`, body, { responseType: 'text' })
  }
  
  logout() {
    this.isAuthenticated = false;
    localStorage.removeItem("token");
    this.router.navigateByUrl("/login");
  }
}  
