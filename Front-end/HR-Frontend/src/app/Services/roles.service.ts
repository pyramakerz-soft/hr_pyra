import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RoleModel } from '../Models/role-model';
import { catchError, Observable, throwError } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class RolesService {
  
  baseurl ="http://127.0.0.1:8000/api/roles"; 

  constructor(public http: HttpClient) { }

  getall(): Observable<RoleModel[]> {
    return this.http.get<RoleModel[]>(this.baseurl);
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


  private handleError(error: any): Observable<never> {
    // Implement your error handling logic here
    console.error('An error occurred:', error);
    return throwError(() => new Error('Something went wrong; please try again later.'));
  }

  
}
