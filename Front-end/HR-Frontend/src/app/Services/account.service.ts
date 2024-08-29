import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from './api.service';


@Injectable({
  providedIn: 'root'
})
export class AccountService {

  isAuthenticated = !!localStorage.getItem("token"); // Check if token exists

  baseUrl = ""

  r: { name: string, job_title: string, id: string, image: string, role_name: string , is_clocked_out :string ,national_id:string, clockIn:string , work_home:boolean ,total_hours:string} = { name: "", job_title: "", id: "", image: "",role_name:"" , is_clocked_out :"",national_id:"" ,clockIn:"" , work_home:false ,total_hours:""};

  constructor(public http: HttpClient, private router: Router , public Api:ApiService) {
    this.baseUrl=Api.BaseUrl+"/auth"

    this.CheckToken();
  }

  public CheckToken(): void {
    const token = localStorage.getItem("token");
    if (token) {
      this.isAuthenticated = true;
      this.GetDataFromToken().subscribe(
        (d: string) => {
          const response = JSON.parse(d);
          const userDetails = response.User;
          this.r = userDetails;
        },
        (error: HttpErrorResponse) => {
          this.logout();
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
    localStorage.removeItem("role");
    this.router.navigateByUrl("Login");
  }
}  

function jwt_decode(token: string): any {
  console.log('Function not implemented.');
}

