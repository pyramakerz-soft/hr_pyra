import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Department } from '../Models/department';
import { catchError, Observable, throwError } from 'rxjs';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class DepartmentService {

  baseurl:string = "";
  token: string = ""

  constructor(private route: ActivatedRoute, public http: HttpClient,public Api:ApiService) {

    this.baseurl=Api.BaseUrl+"/departments"

   }


  getall(): Observable<Department[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Department[]>(this.baseurl, { headers });
  }

  deleteById(id: number): Observable<any> {
    const url = `${this.baseurl}/${id}`;
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.delete(url, { headers });
  }


  createDepartment(name: string, managerId: number ,Is_location_time :number): Observable<Department> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    const body = {
      name: name,
      manager_id: managerId,
      is_location_time:Is_location_time
    };

    return this.http.post<Department>(this.baseurl, body, { headers });
  }

  GetByID(ID: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Department[]>(`${this.baseurl}/${ID}`, { headers });
  }

  UpdateDept(ID: number ,name:string ,managerId:number ,Is_location_time :number){
    const token = localStorage.getItem("token");
    const body = {
      name: name,
      manager_id: managerId,
      is_location_time:Is_location_time

    };
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<Department[]>(`${this.baseurl}/${ID}`,body, { headers });

  }

}
