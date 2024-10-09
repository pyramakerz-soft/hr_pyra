import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { EmployeeDashboard } from '../Models/employee-dashboard';

@Injectable({
  providedIn: 'root'
})
export class EmployeeDashService {

  baseUrl = "http://127.0.0.1:8000/api"


  constructor(public http: HttpClient, private router: Router) {

  }  
  ngOnInit():void{
    const token = localStorage.getItem("token");
  }

  GetClocks(token: string,pageNumber:number): Observable<EmployeeDashboard[]> {
    
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<EmployeeDashboard[]>(`${this.baseUrl}/user_clocks?page=${pageNumber}`, { headers });
  }
  

  





  
}
