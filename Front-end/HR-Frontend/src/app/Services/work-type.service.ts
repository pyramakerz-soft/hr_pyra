import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class WorkTypeService {

  baseURL = ""

  constructor(public http:HttpClient , public Api:ApiService) { 
    this.baseURL=Api.BaseUrl
  }

  getall(): Observable<WorkerType[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<WorkerType[]>(this.baseURL + `/work_types`, { headers });
  }

}
