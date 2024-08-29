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
    return this.http.get<RoleModel[]>(url);
  }


  deleteById(id: number): Observable<any> {
    const url = `${this.baseurl}/${id}`;
    return this.http.delete(url, { responseType: 'text' }).pipe(
      catchError(this.handleError)
    );
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
  

  private handleError(error: any): Observable<never> {
    console.error('An error occurred:', error);
    return throwError(() => new Error('Something went wrong; please try again later.'));
  }

  
}
