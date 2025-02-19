import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Manager } from '../Models/manager';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class ManagersService {
  baseurl =""; 

  constructor(public http: HttpClient , public Api:ApiService) { 
    this.baseurl=Api.BaseUrl+"/users/manager_names"

  }

  getall(): Observable<Manager[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Manager[]>(this.baseurl, { headers });
  }
}
