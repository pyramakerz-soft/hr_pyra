import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RoleModel } from '../Models/role-model';
import { PermissionModel } from '../Models/permission-model';
import { Observable } from 'rxjs';
import { PermissionAddModel } from '../Models/permission-add-model';

@Injectable({
  providedIn: 'root',
})
export class PermissionsService {
  baseurl = 'http://127.0.0.1:8000/api/permissions';

  token =
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MjM0NDY2MTEsImV4cCI6MTcyMzQ1MDIxMSwibmJmIjoxNzIzNDQ2NjExLCJqdGkiOiJCTFVYbGxKR3JuWEY1am5hIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.-07HgHebef-1NoR4dRDa9LPyhuCL5e1UeeIl6--cUhE';

  constructor(public http: HttpClient) {}

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
