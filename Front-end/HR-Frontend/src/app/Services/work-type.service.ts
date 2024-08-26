import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class WorkTypeService {

  baseURL = "http://127.0.0.1:8000/api"

  constructor(public http:HttpClient) { }

  getall(): Observable<WorkerType[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<WorkerType[]>(this.baseURL + `/work_types`, { headers });
  }

}
