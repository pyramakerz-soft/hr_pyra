import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const doNotNavigateToLoginIfTokenExistsGuard: CanActivateFn = (route, state) => {
  let token = localStorage.getItem("token")
  let role = localStorage.getItem("role")
  const router = inject(Router); 

  if(token != null && role === "Employee"){
    router.navigateByUrl('employee');
    return false;
  }
  else if(token != null && role === "Hr"){
    router.navigateByUrl('HR');
    return false;
  }
  else if(token != null && role === "Team leader"){
    router.navigateByUrl('HR');
    return false;
  }
  else if(token != null && role === "Admin"){
    router.navigateByUrl('HR');
    return false;
  }
  
  return true
};
