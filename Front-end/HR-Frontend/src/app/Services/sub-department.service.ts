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
  deptId: number = 0;  // Variable to store the department ID

  constructor(private route: ActivatedRoute, public http: HttpClient,public Api:ApiService) {


   }

   // Method to set the DeptId
  setDeptId(deptId: number) {
    this.deptId = deptId;
    this.baseurl = this.Api.BaseUrl + `/departments/${deptId}/sub-departments`;
  }


  getall( deptId : Number): Observable<SubDepartment[]> {

    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<SubDepartment[]>(this. baseurl, { headers });
  }

  deleteById(id: number): Observable<any> {
    const url = `${this.baseurl}/${id}`;
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.delete(url, { headers });
  }


  createDepartment(name: string, teamLeadId: number , ): Observable<SubDepartment> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    const body = {
      name: name,
      teamlead_id: teamLeadId,
    };

    return this.http.post<SubDepartment>(this.baseurl, body, { headers });
  }

  GetByID(ID: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<SubDepartment[]>(`${this.baseurl}/${ID}`, { headers });
  }

  UpdateDept(ID: number ,name: string, teamLeadId: number , ){
    const token = localStorage.getItem("token");
    const body = {
      name: name,
      teamlead_id: teamLeadId,

    };
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<SubDepartment[]>(`${this.baseurl}/${ID}`,body, { headers });

  }

}
