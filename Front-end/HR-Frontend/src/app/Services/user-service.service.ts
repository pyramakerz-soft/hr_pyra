import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { UserModel } from '../Models/user-model';
import { Observable } from 'rxjs';
import { AddEmployee } from '../Models/add-employee';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = ""

  constructor(public http:HttpClient , public Api:ApiService) {
    this.baseURL=Api.BaseUrl

   }

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

    let formData = new FormData();
    formData.append('image', emp.image as File );
    formData.append('name', emp.name);
    formData.append('department_id', emp.department_id?.toString() || '');
    formData.append('emp_type', emp.emp_type);
    formData.append('phone', emp.phone);
    formData.append('contact_phone', emp.contact_phone);
    formData.append('email', emp.email);
    formData.append('password', emp.password);
    formData.append('national_id', emp.national_id);
    formData.append('hiring_date', emp.hiring_date ? emp.hiring_date.toString() : '');
    formData.append('salary', emp.salary?.toString() || '');
    formData.append('overtime_hours', emp.overtime_hours?.toString() || '');
    formData.append('working_hours_day', emp.working_hours_day?.toString() || '');
    formData.append('start_time', emp.start_time || '');
    formData.append('end_time', emp.end_time || '');
    formData.append('gender', emp.gender);
    
    emp.roles.forEach((role, index) => formData.append(`roles[${index}]`, role));
    emp.location_id.forEach((id, index) => formData.append(`location_id[${index}]`, id.toString()));
    emp.work_type_id.forEach((id, index) => formData.append(`work_type_id[${index}]`, id.toString()));

    return this.http.post<any>(this.baseURL + "/auth/create_user", formData, { headers });
  }

  updateUser(emp:AddEmployee, empId:number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    let formData = new FormData();  
    if(typeof emp.image == "string"){
      formData.append('image', emp.image as string );
    } else{
      formData.append('image', emp.image as File );
    }
    formData.append('name', emp.name);
    formData.append('department_id', emp.department_id?.toString() || '');
    formData.append('emp_type', emp.emp_type);
    formData.append('phone', emp.phone);
    formData.append('contact_phone', emp.contact_phone);
    formData.append('email', emp.email);
    formData.append('national_id', emp.national_id);
    formData.append('hiring_date', emp.hiring_date ? emp.hiring_date.toString() : '');
    formData.append('salary', emp.salary?.toString() || '');
    formData.append('overtime_hours', emp.overtime_hours?.toString() || '');
    formData.append('working_hours_day', emp.working_hours_day?.toString() || '');
    formData.append('start_time', emp.start_time || '');
    formData.append('end_time', emp.end_time || '');
    formData.append('gender', emp.gender);
    emp.roles.forEach((role, index) => formData.append(`roles[${index}]`, role));
    emp.location_id.forEach((id, index) => formData.append(`location_id[${index}]`, id.toString()));
    emp.work_type_id.forEach((id, index) => formData.append(`work_type_id[${index}]`, id.toString()));

    return this.http.post<any>(this.baseURL + "/auth/update_user/" + empId, formData, { headers });
  }
  
  updatePassword(pass:string, empId:number){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    const body={
      password: pass
    }
    return this.http.post<any>(this.baseURL + "/auth/update_password/" + empId, body, { headers });
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

  DeleteById(id:number): Observable<AddEmployee> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.delete<any>(this.baseURL + "/auth/delete_user/" + id, { headers });
  }
}
