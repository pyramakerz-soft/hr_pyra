import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RoleModel } from '../Models/role-model';
import { catchError, Observable, throwError } from 'rxjs';
import { PermissionAddModel } from '../Models/permission-add-model';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class RolesService {
  
  baseurl =""; 
  token:string=""

  constructor(public http: HttpClient , public Api:ApiService) {
    this.baseurl=Api.BaseUrl+"/roles"
  }

  getall(): Observable<RoleModel[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<RoleModel[]>(this.baseurl, { headers });
  }

  getById(id: number): Observable<RoleModel[]> {
    const url = `${this.baseurl}/${id}`;
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<RoleModel[]>(url, { headers });
  }
 
  createRole(name: string, per: string[]): Observable<RoleModel> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    const body = {
      name: name,
      permission: per
    };

    return this.http.post<RoleModel>(this.baseurl, body, { headers });
  }

  updateRole(name: string, per: string[], roleId:number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    const body = {
      name: name,
      permission: per
    };

    return this.http.post<any>(`${this.baseurl}/${roleId}`, body, { headers });
  }

  DeleteByID(id:number){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.delete(`${this.baseurl}/${id}`, { headers, responseType: 'json' });
  }
}
