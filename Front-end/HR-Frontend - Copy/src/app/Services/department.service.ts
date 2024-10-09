import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Department } from '../Models/department';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class DepartmentService {

  baseurl ="http://127.0.0.1:8000/api/departments"; 
  token:string=""

  constructor(public http: HttpClient) { }

  getall(): Observable<Department[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Department[]>(this.baseurl, { headers });
  }
}
