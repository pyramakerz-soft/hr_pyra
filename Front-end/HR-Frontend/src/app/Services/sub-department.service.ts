import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Observable } from 'rxjs';
import { SubDepartment } from '../Models/sub-department';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class SubDepartmentService {

  baseurl:string = "";
  token: string = ""

  constructor(private route: ActivatedRoute, public http: HttpClient,public Api:ApiService,) {

    this.baseurl=Api.BaseUrl+`/departments`

   }


  getall( deptId : Number): Observable<SubDepartment[]> {
        const url = `${this.baseurl}/${deptId}/sub-departments`;

    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<SubDepartment[]>(url, { headers });
  }

  // deleteById(id: number): Observable<any> {
  //   const url = `${this.baseurl}/${id}`;
  //   const token = localStorage.getItem("token");
  //   const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
  //   return this.http.delete(url, { headers });
  // }


  // createDepartment(name: string, managerId: number ,Is_location_time :number): Observable<SubDepartment> {
  //   const token = localStorage.getItem("token");
  //   const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

  //   const body = {
  //     name: name,
  //     manager_id: managerId,
  //     is_location_time:Is_location_time
  //   };

  //   return this.http.post<SubDepartment>(this.baseurl, body, { headers });
  // }

  // GetByID(ID: number) {
  //   const token = localStorage.getItem("token");
  //   const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
  //   return this.http.get<SubDepartment[]>(`${this.baseurl}/${ID}`, { headers });
  // }

  // UpdateDept(ID: number ,name:string ,managerId:number ,Is_location_time :number){
  //   const token = localStorage.getItem("token");
  //   const body = {
  //     name: name,
  //     manager_id: managerId,
  //     is_location_time:Is_location_time

  //   };
  //   const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
  //   return this.http.post<SubDepartment[]>(`${this.baseurl}/${ID}`,body, { headers });

  // }

}
