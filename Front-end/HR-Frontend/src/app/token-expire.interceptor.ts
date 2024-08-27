import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpErrorResponse } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable()
export class TokenExpireInterceptor implements HttpInterceptor {

  constructor(private router: Router) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = localStorage.getItem('token');
    let authReq = req;

    // If token exists, clone the request and add the token
    if (token) {
      authReq = req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
      });
    }

    // Handle request
    return next.handle(authReq).pipe(
      catchError((error: HttpErrorResponse) => {
        // If the server responds with 401, handle token expiry
        if (error.status === 401) {
          // Remove the expired token
          localStorage.removeItem('token');
          // Redirect to login page
          this.router.navigate(['/login']);
        }
        // Pass the error to the next handler
        return throwError(error);
      })
    );
  }
}
