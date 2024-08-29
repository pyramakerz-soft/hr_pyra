import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Manager } from '../Models/manager';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ManagersService {
  baseurl ="http://127.0.0.1:8000/api/department_manager_names"; 

  constructor(public http: HttpClient) { }

  getall(): Observable<Manager[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Manager[]>(this.baseurl, { headers });
  }
}
