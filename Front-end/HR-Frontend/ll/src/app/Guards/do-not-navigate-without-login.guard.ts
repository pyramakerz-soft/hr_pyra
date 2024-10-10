import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const doNotNavigateWithoutLoginGuard: CanActivateFn = (route, state) => {
  let token = localStorage.getItem("token")
  const router = inject(Router);

  if(token == null){
    router.navigateByUrl('/Login');
    return false;
  }
  return true
};
