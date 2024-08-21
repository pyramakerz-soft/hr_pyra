import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { UserModel } from '../Models/user-model';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = "http://127.0.0.1:8000/api"

  constructor(public http:HttpClient) { }


  getall(): Observable<UserModel[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<UserModel[]>(this.baseURL+"/auth/getAllUsers?page=1", { headers });
  }

}
