import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const navigateIfEmployeeGuard: CanActivateFn = (route, state) => {
  let token = localStorage.getItem("token")
  let role = localStorage.getItem("role")
  const router = inject(Router); 

  if(token != null && role !== "Employee"){
    router.navigateByUrl('HR');
    return false;
  }
  return true
};
