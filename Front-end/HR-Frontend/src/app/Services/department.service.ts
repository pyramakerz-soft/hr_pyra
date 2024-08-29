import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Department } from '../Models/department';
import { catchError, Observable, throwError } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class DepartmentService {

  baseurl ="http://127.0.0.1:8000/api/departments"; 
  token:string=""

  constructor(public http: HttpClient) { }

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


  createDepartment(name: string, managerId: number): Observable<Department> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    const body = {
      name: name,
      manager_id: managerId
    };

    return this.http.post<Department>(this.baseurl, body, { headers });
  }

  private handleError(error: any): Observable<never> {
    console.error('An error occurred:', error);
    return throwError(() => new Error('Something went wrong; please try again later.'));
  }



}
