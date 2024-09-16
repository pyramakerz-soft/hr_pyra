import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve, RouterStateSnapshot } from '@angular/router';
import { UserServiceService } from '../user-service.service';

@Injectable({
  providedIn: 'root'
})
export class UserDataService implements Resolve<any> {

  constructor(private userService:UserServiceService) { }

  resolve(route: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    let userId = Number(route.paramMap.get('Id'));
    return this.userService.getUserById(userId);
  }
}
