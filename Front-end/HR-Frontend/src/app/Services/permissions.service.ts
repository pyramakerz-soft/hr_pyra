import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RoleModel } from '../Models/role-model';
import { PermissionModel } from '../Models/permission-model';
import { Observable } from 'rxjs';
import { PermissionAddModel } from '../Models/permission-add-model';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root',
})
export class PermissionsService {
  baseurl = '';

  token =""
  constructor(public http: HttpClient , public Api:ApiService) {
    this.baseurl=Api.BaseUrl+"/permissions"

  }

  GetAll() {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<PermissionModel[]>(`${this.baseurl}`, { headers }); // Ensure URL path is correct
  }


  GetByID(id:number){
    const headers = new HttpHeaders({
      Authorization: `Bearer ${this.token}`, // Use backticks for template literals
    });
    return this.http.get<PermissionModel>(`${this.baseurl}/${id}`, { headers }); // Ensure URL path is correct
  }

  Create(permission: PermissionAddModel): Observable<PermissionAddModel> {
    const headers = new HttpHeaders({
      Authorization: `Bearer ${this.token}`,
      'Content-Type': 'application/json',
    });
    return this.http.post<PermissionAddModel>(this.baseurl, permission, { headers });
  }


  Update(id: number, permission: PermissionModel): Observable<PermissionModel> {
    const headers = new HttpHeaders({
      Authorization: `Bearer ${this.token}`,
      'Content-Type': 'application/json',
    });
    return this.http.post<PermissionModel>(`${this.baseurl}/${id}`, permission, { headers });
  }


  Delete(id: number): Observable<void> {
    const headers = new HttpHeaders({
      Authorization: `Bearer ${this.token}`,
    });
    return this.http.delete<void>(`${this.baseurl}/${id}`, { headers });
  }


}
