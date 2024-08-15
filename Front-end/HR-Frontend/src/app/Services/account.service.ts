import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class AccountService {

  isAuthenticated = !!localStorage.getItem("token"); // Check if token exists

  url="http://127.0.0.1:8000/api/auth/login"

  constructor() { }

  private CheckToken(): void {
    const token = localStorage.getItem("token");
    if (token) {
      this.isAuthenticated = true;
      // this.r = jwtDecode(token);

      
    } else {
      this.isAuthenticated = false;
    }
  }


}
