import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { UserModel } from '../Models/user-model';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = "http://127.0.0.1:8000/api"
  token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MjM0NDg0MTMsImV4cCI6MTcyMzQ1MjAxMywibmJmIjoxNzIzNDQ4NDEzLCJqdGkiOiJ6V0NwMXpBY0xNWnZ4QUdGIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.XMJgk4zgYaQ2ovmu4KVUJIrNafzt5QPu3UE3wh8c7eA"

  constructor(public http:HttpClient) { }

  GetAllusers(){
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${this.token}`
    });
    
    return this.http.get<UserModel>(this.baseURL+"/auth/users", {headers})
  }

  DeleteUser(id: number){
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${this.token}`
    });
    
    return this.http.delete(this.baseURL+"/auth/users/"+id, {headers})
  }
}
