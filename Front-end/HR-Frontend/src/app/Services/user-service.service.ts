import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { UserModel } from '../Models/user-model';
import { Observable } from 'rxjs';
import { AddEmployee } from '../Models/add-employee';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = "http://127.0.0.1:8000/api"

  constructor(public http:HttpClient) { }

  getall(pageNumber:number): Observable<UserModel[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<UserModel[]>(this.baseURL + `/auth/getAllUsers?page=${pageNumber}`, { headers });
  }

  getUserById(id:number): Observable<AddEmployee> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<AddEmployee>(this.baseURL + "/auth/get_user_by_id/" + id, { headers });
  }
  
  createUser(emp:AddEmployee) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    return this.http.post<any>(this.baseURL + "/auth/create_user", emp, { headers });
  }

  updateUser(emp:AddEmployee, empId:number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    return this.http.post<any>(this.baseURL + "/auth/update_user/" + empId, emp, { headers });
  }
  

  SearchByName(Name:string){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<UserModel[]>(this.baseURL + `/auth/getAllUsers?search=${Name}`, { headers });
  }

  getAllUsersName(){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<UserModel[]>(this.baseURL + `/auth/users_by_name`, { headers });
  }
}
