import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const navigateIfHrGuard: CanActivateFn = (route, state) => {
  let token = localStorage.getItem("token")
  let role = localStorage.getItem("role")
  const router = inject(Router); 

  if(token != null && role !== "Hr" && role !== "Admin"){
    router.navigateByUrl('employee');
    return false;
  }
  return true
};
