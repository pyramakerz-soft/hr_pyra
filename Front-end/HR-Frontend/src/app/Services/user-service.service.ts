import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { UserModel } from '../Models/user-model';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = "http://127.0.0.1:8000/api"
  token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MjM0Mzc5MzcsImV4cCI6MTcyMzQ0MTUzNywibmJmIjoxNzIzNDM3OTM3LCJqdGkiOiJTbFNxaUR1NmRtVGJKS0RwIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.FMNbwJA0sfpYsNrxQDqUP6r6rWd4e48073hz-GffRCc"

  constructor(public http:HttpClient) { }

  GetAllusers(){
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${this.token}`
    });
    
    return this.http.get<UserModel>(this.baseURL+"/auth/users", {headers})
  }
}
