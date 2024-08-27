import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AccountService } from '../Services/account.service';
import { HttpErrorResponse } from '@angular/common/http';

export const doNotNavigateToLoginIfTokenGuard: CanActivateFn = (route, state) => {
  let token = localStorage.getItem("token")
  const router = inject(Router);
  let account = inject(AccountService);

  let r: { name: string, job_title: string, id: string, image: string, role_name: string , is_clocked_out :string ,national_id:string, clockIn:string} = { name: "", job_title: "", id: "", image: "",role_name:"" , is_clocked_out :"",national_id:"" ,clockIn:""};

  if(token != null){
    account.GetDataFromToken().subscribe(
      (d: string) => {
        const response = JSON.parse(d);
        const userDetails = response.User;
        r = userDetails;
        if(r.role_name === "Employee"){
          router.navigateByUrl('employee');
        } else if(r.role_name === "Hr"){
          router.navigateByUrl('HR');
        }
        return false;
      },
      (error: HttpErrorResponse) => {
        account.logout()
      }
    );
  }
  return true
};
